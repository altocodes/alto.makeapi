<?php

namespace Alto\MakeApi\Repository;


use Alto\MakeApi\Dto\Iblock\ElementDto;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\Iblock\Property\PropertyValueDto;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\IblockHelper;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\SystemException;

class IblockRepository
{
    public $properties;

    private Entity $entity;
    private $dataClass;

    public function __construct(string $code)
    {
        if (!Loader::includeModule('iblock')) {
            throw new RepositoryException(Loc::getMessage('ALTO_STRAPI_REPOSITORY_EXCEPTION_MODULES_NOT_INSTALL'));
        }

        if (!$this->entity = IblockTable::compileEntity($code)) {
            throw new RepositoryException(Loc::getMessage('ALTO_STRAPI_REPOSITORY_EXCEPTION_COMPILATION_ERROR', ['#API_CODE#' => $code]));
        }

        $this->dataClass = $this->entity->getDataClass();
        $this->properties = $this->getProperties();
    }

    /**
     * Получение информации об инфоблоке и его свойствах
     * @return IblockDto
     */
    public function getIblock(): IblockDto
    {
        $this->entity->getIblock()->fill();
        $fields = $this->entity->getIblock()->collectValues();
        if (count($this->properties) > 0) {
            $fields['PROPERTIES'] = $this->properties;
        }

        return IblockDto::fromArray($fields);
    }

    /**
     * Получение списка элементов
     * @param array $params
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getList(array $params): array
    {
        $elements = [];
        $data = $this->getElementsData($params);

        foreach ($data as $item) {
            $item['DETAIL_PAGE_URL'] = $this->getDetailUrl($item);

            foreach ($item['PROPERTY_VALUES'] as $code => $value) {
                $prop = $this->properties[$code];

                // TODO: подумать как лучше преобразовывать значение
                $value = IblockHelper::parseValue($prop, $value);

                $prop['VALUE'] = $value;
                $prop = PropertyValueDto::fromArray($prop);

                $item['PROPERTY_VALUES'][$code] = $prop;
            }
            unset($value);

            $elements[] = ElementDto::fromArray($item);
        }

        return $elements;
    }

    /**
     * Получение всего кол-ва элементов
     * @param array $filter
     * @return int
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCount(array $filter)
    {
        return $this->dataClass::getCount($filter);
    }

    /**
     * Получение элементов инфоблока
     *
     * @param array $filter
     * @param array $select
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getElementsData(array $params): array
    {
        $elements = [];

        $select = $params['select'] ?? ['*'];
        $filter = $params['filter'] ?? [];
        $limit = $params['limit'] ?? false;
        $offset = $params['offset'] ?? false;
        $order = $params['order'] ?? ['ID'];

        $propertyCodes = array_keys($this->properties);
        $select = array_merge($select, $propertyCodes);

        $collection = $this->dataClass::getList([
            'select' => $select,
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $offset,
            'order' => $order
        ])->fetchCollection();
        foreach ($collection as $item) {
            $element = [];

            // TODO: проблема со свойствами, которые названы аналогично стандартным полям
            // 1. они не выбираются в коллекции
            // 2. после проверки in_array попадает обычное поле в PROPERTY_VALUES
            // 3. с алиасами коллекции не работают, поля возвращаются как есть, без алиасов
            $fields = $item->collectValues();
            foreach ($fields as $code => $field) {
                if (in_array($code, $propertyCodes)) {
                    $element['PROPERTY_VALUES'][$code] = $this->getPropertyValue($field);
                } else {
                    $element[$code] = $field;
                }
            }

            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * Получение списка свойств
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getProperties(): array
    {
        $properties = $this->entity->getIblock()->getProperties()->getAll();
        foreach ($properties as $key => $property) {
            $fields = $property->collectValues();

            switch ($fields['PROPERTY_TYPE']) {
                case PropertyTable::TYPE_LIST:
                    $fields['ITEMS'] = PropertyEnumerationTable::getList([
                        'filter' => [
                            'PROPERTY_ID' => $property['ID'],
                        ],
                        'cache' => ['ttl' => 36000]
                    ])->fetchAll();
                    break;
                default:
                    if (isset($fields['USER_TYPE']) && $fields['USER_TYPE'] === 'directory') {
                        $settings = unserialize($fields['USER_TYPE_SETTINGS']);
                        $table = $settings['TABLE_NAME'];

                        $hl = HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => $table]])->fetch();
                        if ($hl) {
                            $hlEntity = HighloadBlockTable::compileEntity($hl);
                            $entityDataClass = $hlEntity->getDataClass();

                            $fields['ITEMS'] = $entityDataClass::getList()->fetchAll();
                        }
                    }
            }

            $properties[$property->getCode()] = $fields;
            unset($properties[$key]);
        }

        return $properties;
    }

    /**
     * Обработка значений свойств
     *
     * @param $value
     * @return array|mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function getPropertyValue($value)
    {
        $new = null;
        switch (true) {
            case $value instanceof Collection:

                $collection = $value->getAll();
                foreach ($collection as $item) {
                    $new[] = $this->getPropertyValue($item);
                }

                break;
            case $value instanceof ValueStorage:
                $new = $value->collectValues();
                break;
            default:
                $new = $value;
        }

        return $new;
    }

    /**
     * Получение ЧПУ детальной страницы
     *
     * @param array $values
     * @return string
     */
    protected function getDetailUrl(array $values): string
    {
        return \CIBlock::ReplaceDetailUrl($this->entity->getIblock()->fillDetailPageUrl(), $values, false, 'E');
    }

    /**
     * Инициализация репозитория
     *
     * @param string $code
     * @return self
     * @throws RepositoryException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function factory(string $code): self
    {
        $iblock = IblockHelper::getIblockByCode($code, ['API_CODE']);
        if (!$iblock) {
            throw new RepositoryException(Loc::getMessage('ALTO_STRAPI_REPOSITORY_EXCEPTION_NOT_FOUND_IBLOCK', ['#CODE#' => $code]));
        }

        if (!$iblock['API_CODE']) {
            throw new RepositoryException(Loc::getMessage('ALTO_STRAPI_REPOSITORY_EXCEPTION_INVALID_API_CODE', ['#CODE#' => $code]));
        }

        return new self($iblock['API_CODE']);
    }
}