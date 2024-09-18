<?php

namespace Alto\MakeApi\Helper;

use Alto\MakeApi\Dto\Iblock\Property\Value\DirectoryValueDto;
use Alto\MakeApi\Dto\Iblock\Property\Value\ElementValueDto;
use Alto\MakeApi\Dto\Iblock\Property\Value\ListValueDto;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

Loader::includeModule('iblock');

class IblockHelper
{
    /**
     * Код типа пользовательского свойства
     */
    const PROPERTY_USER_FIELD = 'USER_FIELD';

    /**
     * Получение информации об инфоблоке по символьному коду
     *
     * @param string $code
     * @param array $select
     * @return array|false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIblockByCode(string $code, array $select = ['*'])
    {
        return IblockTable::getList([
            'select' => $select,
            'filter' => ['CODE' => $code]
        ])->fetch();
    }

    /**
     * Преобразование значения в читабельный вид
     * @param array $property
     * @param $value
     * @return \Alto\MakeApi\Dto\BaseDto|DirectoryValueDto|ElementValueDto|ListValueDto|mixed|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function parseValue(array $property, $value)
    {
        if (is_array($value) && isset($value['VALUE'])) {
            $value = $value['VALUE'];
        }

        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                if (is_array($val) && isset($val['VALUE'])) {
                    $val = $val['VALUE'];
                }

                $val = self::parseValue($property, $val);
            }
            unset($val);
        } else {
            switch ($property['PROPERTY_TYPE']) {
                case PropertyTable::TYPE_FILE:
                    $value = FetcherHelper::getById((int)$value);
                    break;
                case PropertyTable::TYPE_SECTION:
                    if ($section = SectionTable::getById((int)$value)->fetch()) {
                        $value = $section['NAME'];
                    }
                    break;
                case PropertyTable::TYPE_LIST:
                    foreach ($property['ITEMS'] as $item) {
                        if ($value == $item['ID'] || $value == $item['XML_ID']) {
                            $value = ListValueDto::fromArray($item);
                            break;
                        }
                    }

                    if ($value === 0) {
                        $value = null;
                    }

                    break;
                case PropertyTable::TYPE_ELEMENT:
                    $element = ElementTable::getList([
                        'filter' => ['ID' => (int)$value],
                        'cache' => ['ttl' => 36000]
                    ])->fetch();

                    if ($element) {
                        $value = ElementValueDto::fromArray($element);
                    }
                    break;
                case self::PROPERTY_USER_FIELD:
                    global $USER_FIELD_MANAGER;

                    $idType = $property['USER_TYPE_ID'] ?? $property['USER_TYPE'];
                    $userType = $USER_FIELD_MANAGER->GetUserType($idType);
                    if ($userType) {
                        $property['USER_TYPE'] = $userType;
                        if (is_callable([$userType['CLASS_NAME'], 'renderView'])) {
                            $value = call_user_func_array(
                                [$userType['CLASS_NAME'], 'renderView'],
                                [
                                    $property,
                                    ['VALUE' => $value],
                                ]
                            );
                        }
                    }
                    break;
                default:
                    if (isset($property['USER_TYPE']) && !empty($property['USER_TYPE'])) {
                        switch ($property['USER_TYPE']) {

                            case 'directory':
                                foreach ($property['ITEMS'] as $item) {
                                    if ($item['UF_XML_ID'] === $value) {
                                        if ($item['UF_FILE']) {
                                            $item['UF_FILE'] = FetcherHelper::getById($item['UF_FILE']);
                                        }
                                        $value = DirectoryValueDto::fromArray($item);
                                    }
                                }
                                break;
                            default:
                                $userType = \CIBlockProperty::GetUserType($property['USER_TYPE']);

                                if (isset($userType['GetAdminListViewHTML']) && is_callable($userType['GetAdminListViewHTML'])) {
                                    if (!empty($value)) {
                                        $value = call_user_func_array(
                                            $userType["GetAdminListViewHTML"],
                                            [
                                                $property,
                                                ["VALUE" => $value],
                                                [],
                                            ]
                                        );
                                    }
                                }
                        }
                    }
            }
        }

        return $value;
    }

    /**
     * Приведение фильтра
     * @param array $filter
     * @return array
     */
    public static function prepareFilter(array $filter)
    {
        $preparedFilter = [];

        foreach ($filter as $key => $value) {
            // TODO: добавить обработку условия ИЛИ, например, ID => '2|3|5', PROPERTY_COLOR => 'red,blue|yellow,black'

            $values = array_filter(explode(',', $value));
            $preparedFilter[$key] = count($values) > 1 ? $values : $value;
        }

        return $preparedFilter;
    }
}