<?php

namespace Alto\MakeApi\Helper;

use Alto\MakeApi\Dto\Entity\Iblock\Element\ElementShortDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\Value\DirectoryValueDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\Value\ElementValueDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\Value\ListValueDto;
use Alto\MakeApi\Dto\UserType\BlockContentDto;
use Alto\MakeApi\UserType\BlockContentProperty;
use Alto\MakeApi\UserType\ImageHotspotsProperty;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\PropertyEnumerationTable;

Loader::includeModule('iblock');

class IblockHelper
{
    /**
     * Код типа пользовательского свойства
     */
    const PROPERTY_USER_FIELD = 'USER_FIELD';

    /**
     * Преобразование значения в читабельный вид
     * @param array $property
     * @param mixed $value
     * @param int $depth Глубина рекурсии (для предотвращения бесконечной рекурсии)
     * @param array $fileCache Предзагруженный кэш файлов [fileId => BaseDto]
     * @return mixed
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function parseValue(array $property, $value, int $depth = 0, array $fileCache = [])
    {
        // Защита от бесконечной рекурсии
        if ($depth > 2) {
            return null;
        }



        if (is_array($value) && isset($value['VALUE'])) {
            $value = $value['VALUE'];
        }

        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            $result = [];
            
            // Для файлов собираем все ID для массовой загрузки
            if ($property['PROPERTY_TYPE'] === PropertyTable::TYPE_FILE) {
                $fileIds = [];
                foreach ($value as $item) {
                    if (is_numeric($item)) {
                        $fileIds[] = (int)$item;
                    }
                }
                
                if (!empty($fileIds)) {
                    // Получаем файлы из переданного кэша или загружаем недостающие
                    $loadedFiles = [];
                    $filesToLoad = [];
                    
                    foreach ($fileIds as $fileId) {
                        if (isset($fileCache[$fileId])) {
                            $loadedFiles[$fileId] = $fileCache[$fileId];
                        } else {
                            $filesToLoad[] = $fileId;
                        }
                    }
                    
                    // Если есть файлы, которых нет в кэше, загружаем их массово
                    if (!empty($filesToLoad)) {
                        $newFiles = FetcherHelper::getFilesByIds($filesToLoad);
                        foreach ($newFiles as $fileId => $file) {
                            $loadedFiles[$fileId] = $file;
                        }
                    }
                    
                    // Формируем результат в правильном порядке
                    foreach ($fileIds as $fileId) {
                        if (isset($loadedFiles[$fileId])) {
                            $result[] = $loadedFiles[$fileId];
                        }
                    }
                    
                    return $result;
                }
            }
            
            // Для других типов рекурсивно обрабатываем каждый элемент
            foreach ($value as $val) {
                if (is_array($val) && isset($val['VALUE'])) {
                    $val = $val['VALUE'];
                }
                $result[] = self::parseValue($property, $val, $depth + 1, $fileCache);
            }
            
            return $result;
            
        } else {
            switch ($property['PROPERTY_TYPE']) {
                case PropertyTable::TYPE_FILE:
                    $fileId = (int)$value;
                    
                    // Пробуем взять из кэша
                    if (isset($fileCache[$fileId])) {
                        return $fileCache[$fileId];
                    }
                    
                    // Если нет в кэше, загружаем (возможно массово через getFileById)
                    return FetcherHelper::getFileById($fileId);
                    
                case PropertyTable::TYPE_SECTION:
                    $section = SectionTable::getById((int)$value)->fetch();
                    if ($section) {
                        $value = ElementValueDto::fromArray($section);
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
                        'filter' => [
                            'ID' => (int)$value,
                            '=ACTIVE' => 'Y',
                        ],
                        'cache' => ['ttl' => 36000]
                    ])->fetch();

                    if ($element) {
                        // Получаем свойства привязанного элемента, но только для первого уровня
                        // TODO::подумать как убрать этот костыль, хорошо бы определять какие свойства нужно тянуть
                        if ($depth === 0) {
                            $linkedProperties = self::getLinkedElementProperties($element['IBLOCK_ID'], (int)$value, $depth + 1);
                            $element['PROPERTIES'] = $linkedProperties;
                        } else {
                            // Для вложенных элементов не загружаем свойства
                            $element['PROPERTIES'] = [];
                        }
                        
                        $value = ElementShortDto::fromArray($element);
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
                            case 'HTML':
                                if ($value !== null && $value !== '') {
                                    $value = self::textDtoFromHtmlPropertyValue($value);
                                }
                                break;
                            case 'directory':
                                if (empty($property['ITEMS'])) {
                                    $property['ITEMS'] = self::loadDirectoryItems($property);
                                }

                                foreach ($property['ITEMS'] as $item) {
                                    if ($item['UF_XML_ID'] === $value) {
                                        if ($item['UF_FILE']) {
                                            // Используем кэш файлов если есть
                                            $fileId = (int)$item['UF_FILE'];
                                            if (isset($fileCache[$fileId])) {
                                                $item['UF_FILE'] = $fileCache[$fileId];
                                            } else {
                                                $item['UF_FILE'] = FetcherHelper::getFileById($fileId);
                                            }
                                        }
                                        $value = DirectoryValueDto::fromArray($item);
                                    }
                                }
                                break;
                            case BlockContentProperty::USER_TYPE:

                                $userType = \CIBlockProperty::GetUserType($property['USER_TYPE']);

                                if (isset($userType['GetPublicViewHTML']) && is_callable($userType['GetPublicViewHTML'])) {
                                    if (!empty($value)) {
                                        $value = call_user_func_array(
                                            $userType['GetPublicViewHTML'],
                                            [
                                                $property,
                                                ['VALUE' => $value],
                                                ['VIEW' => 'JSON'],
                                            ]
                                        );
                                    }
                                }
                                break;
                            case ImageHotspotsProperty::USER_TYPE:
                                $userType = \CIBlockProperty::GetUserType($property['USER_TYPE']);
                                if (!empty($value) && isset($userType['GetPublicViewHTML']) && is_callable($userType['GetPublicViewHTML'])) {
                                    $value = call_user_func_array(
                                        $userType['GetPublicViewHTML'],
                                        [
                                            $property,
                                            ['VALUE' => $value],
                                            ['VIEW' => 'JSON'],
                                        ]
                                    );
                                }
                                break;
                            default:
                                $userType = \CIBlockProperty::GetUserType($property['USER_TYPE']);

                                if (isset($userType['GetPublicViewHTML']) && is_callable($userType['GetPublicViewHTML'])) {
                                    if (!empty($value)) {
                                        $value = call_user_func_array(
                                            $userType['GetPublicViewHTML'],
                                            [
                                                $property,
                                                ['VALUE' => $value],
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
     * Одна строка значения свойства из GetProperty: только VALUE / DESCRIPTION (без списка по ID).
     */
    private static function isPlainIblockPropertyValueKeys(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (!in_array($key, ['VALUE', 'DESCRIPTION', '~VALUE', '~DESCRIPTION'], true)) {
                return false;
            }
        }

        return array_key_exists('VALUE', $value);
    }

    /**
     * Значение свойства HTML (S:HTML): сериализованный массив Bitrix с ключами TEXT и TYPE,
     * уже распарсенный массив, либо строка вида type:text / сырое HTML.
     *
     * @param mixed $value
     */
    private static function textDtoFromHtmlPropertyValue($value): TextDto
    {
        if (is_array($value)) {
            $type = isset($value['TYPE']) ? strtolower((string) $value['TYPE']) : 'html';

            return new TextDto($type, (string) ($value['TEXT'] ?? ''));
        }

        $str = (string) $value;
        $decoded = @unserialize($str, ['allowed_classes' => false]);
        if (is_array($decoded) && array_key_exists('TEXT', $decoded)) {
            $type = isset($decoded['TYPE']) ? strtolower((string) $decoded['TYPE']) : 'html';

            return new TextDto($type, (string) $decoded['TEXT']);
        }

        return self::textDtoFromTypePrefixedString($str);
    }

    /**
     * Строка без сериализации: префикс «тип» и текст разделяются первым «:» (как type:text).
     */
    private static function textDtoFromTypePrefixedString(string $raw): TextDto
    {
        $colonPos = strpos($raw, ':');
        if ($colonPos === false) {
            return new TextDto('html', $raw);
        }

        return new TextDto(
            substr($raw, 0, $colonPos),
            substr($raw, $colonPos + 1)
        );
    }

    /**
     * Получение свойств привязанного элемента с их описаниями
     * 
     * @param int $iblockId
     * @param int $elementId
     * @param int $depth Глубина рекурсии
     * @return array
     */
    private static function getLinkedElementProperties(int $iblockId, int $elementId, int $depth = 0): array
    {
        $properties = [];
        
        // Сначала получаем все описания свойств инфоблока
        $propertyDefinitions = self::getPropertyDefinitions($iblockId);
        
        // Затем получаем значения свойств для элемента
        $propertyValues = \CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            ['sort' => 'asc'],
        );
        
        while ($propValue = $propertyValues->Fetch()) {
            $propCode = strtolower($propValue['CODE']);
            
            // Если есть описание свойства, парсим значение
            if (isset($propertyDefinitions[$propCode])) {
                $propertyDef = $propertyDefinitions[$propCode];
                
                // Парсим значение с учетом глубины рекурсии
                $parsedValue = self::parseValue($propertyDef, $propValue['VALUE'], $depth);
                $properties[$propCode] = $parsedValue;
            } else {
                // Если нет описания, возвращаем сырое значение
                $properties[$propCode] = $propValue['VALUE'];
            }
        }
        
        return $properties;
    }

    /**
     * Получение описаний всех свойств инфоблока
     * 
     * @param int $iblockId
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getPropertyDefinitions(int $iblockId): array
    {
        static $cache = [];
        
        if (isset($cache[$iblockId])) {
            return $cache[$iblockId];
        }
        
        $properties = [];
        
        $result = PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
            'order' => ['SORT' => 'ASC']
        ]);
        
        while ($property = $result->fetch()) {
            $propCode = strtolower($property['CODE']);
            
            // Для свойств типа список сразу загружаем элементы
            if ($property['PROPERTY_TYPE'] === PropertyTable::TYPE_LIST) {
                $property['ITEMS'] = PropertyEnumerationTable::getList([
                    'filter' => ['PROPERTY_ID' => $property['ID']],
                    'cache' => ['ttl' => 36000]
                ])->fetchAll();
            }
            
            // Для свойств типа справочник загружаем элементы
            if (isset($property['USER_TYPE']) && $property['USER_TYPE'] === 'directory') {
                $property['ITEMS'] = self::loadDirectoryItems($property);
            }
            
            $properties[$propCode] = $property;
        }
        
        $cache[$iblockId] = $properties;
        
        return $properties;
    }

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
     * TODO::как будто лишнее
     * Приведение фильтра
     * @param array $filter
     * @return array
     */
    public static function prepareFilter(array $filter)
    {
        $preparedFilter = [
            'ACTIVE' => 'Y'
        ];

        foreach ($filter as $key => $value) {
            $values = array_filter(explode(',', $value));
            $preparedFilter[$key] = count($values) > 1 ? $values : $value;
        }

        return $preparedFilter;
    }

    /**
     * Загружает элементы highload-блока для свойств типа directory
     * @param array $property Данные свойства
     * @return array Массив элементов справочника
     */
    private static function loadDirectoryItems(array $property): array
    {
        $items = [];
        
        $settings = $property['USER_TYPE_SETTINGS_LIST'] ?? [];
        $tableName = $settings['TABLE_NAME'] ?? '';
            
        if (empty($tableName)) {
            $serializedSettings = $property['USER_TYPE_SETTINGS'] ?? '';
            if (!empty($serializedSettings)) {
                $unserialized = unserialize($serializedSettings);
                $tableName = $unserialized['TABLE_NAME'] ?? '';
            }
        }
            
        if (empty($tableName)) {
            return $items;
        }
            
        // Получаем highload-блок по названию таблицы
        $highloadBlock = HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => $tableName]
        ])->fetch();
            
        if (!$highloadBlock) {
            return $items;
        }
            
        // Получаем класс сущности
        $entity = HighloadBlockTable::compileEntity($highloadBlock);
        $entityClass = $entity->getDataClass();
            
        // Загружаем все активные элементы
        $itemsResult = $entityClass::getList([
            'select' => ['*'],
            'order' => ['UF_SORT' => 'ASC', 'ID' => 'ASC']
        ]);
            
        while ($item = $itemsResult->fetch()) {
            $items[] = $item;
        }
        var_dump($items);die();
        return $items;
    }



    /**
     * Конвертация XML_ID значения свойства-списка в ID
     * 
     * @param mixed $value Значение для конвертации
     * @param int $propertyId ID свойства
     * @return int|null
     */
    private static function convertXmlIdToEnumId($value, int $propertyId): ?int
    {
        if (empty($value)) {
            return null;
        }
        
        // Пытаемся найти enum по XML_ID
        $enum = PropertyEnumerationTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=PROPERTY_ID' => $propertyId,
                '=XML_ID' => $value
            ],
            'limit' => 1,
            'cache' => ['ttl' => 3600]
        ])->fetch();
        
        if ($enum) {
            return (int)$enum['ID'];
        }
        
        // Если не нашли по XML_ID, проверяем может это VALUE
        $enum = PropertyEnumerationTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=PROPERTY_ID' => $propertyId,
                '=VALUE' => $value
            ],
            'limit' => 1,
            'cache' => ['ttl' => 3600]
        ])->fetch();
        
        return $enum ? (int)$enum['ID'] : null;
    }

    /**
     * Получение ID значения свойства-списка по XML_ID
     *
     * @param int $iblockId ID инфоблока
     * @param string $propertyCode Код свойства (без префикса PROPERTY_)
     * @param string $xmlId XML_ID значения свойства
     * @return int|null ID значения или null если не найдено
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getListPropertyValueIdByXmlId(int $iblockId, string $propertyCode, string $xmlId): ?int
    {
        static $cache = [];
        
        $cacheKey = "{$iblockId}_{$propertyCode}_{$xmlId}";
        
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }
        
        // Получаем ID свойства по коду
        $property = PropertyTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=CODE' => $propertyCode,
                '=PROPERTY_TYPE' => PropertyTable::TYPE_LIST
            ],
            'limit' => 1,
            'cache' => ['ttl' => 3600]
        ])->fetch();
        
        if (!$property) {
            $cache[$cacheKey] = null;
            return null;
        }
        
        $propertyId = (int)$property['ID'];
        
        // Получаем ID значения по XML_ID
        $enum = PropertyEnumerationTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=PROPERTY_ID' => $propertyId,
                '=XML_ID' => $xmlId
            ],
            'limit' => 1,
            'cache' => ['ttl' => 3600]
        ])->fetch();
        
        $result = $enum ? (int)$enum['ID'] : null;
        $cache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * Получение VALUE значения свойства-списка по ID
     *
     * @param int $enumId ID значения свойства
     * @return string|null Значение или null если не найдено
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getListPropertyValueById(int $enumId): ?string
    {
        static $cache = [];
        
        if (isset($cache[$enumId])) {
            return $cache[$enumId];
        }
        
        $enum = PropertyEnumerationTable::getList([
            'select' => ['VALUE'],
            'filter' => ['=ID' => $enumId],
            'limit' => 1,
            'cache' => ['ttl' => 3600]
        ])->fetch();
        
        $result = $enum ? (string)$enum['VALUE'] : null;
        $cache[$enumId] = $result;
        
        return $result;
    }
}