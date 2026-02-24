<?php

namespace Alto\MakeApi\Repository;


use Alto\MakeApi\Entity\Converter\ORM\FilterConverter;
use Alto\MakeApi\Entity\QueryBuilder;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\IblockHelper;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class IblockRepository extends QueryBuilder
{
    const SORT_BY_DEFAULT = 'ID';
    const SORT_ORDER_DEFAULT = 'ASC';
    const CACHE_TIME = 36000;

    protected array $properties;

    public function __construct(string $code)
    {
        if (!Loader::includeModule('iblock')) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_MODULES_NOT_INSTALL'));
        }

        if (!$entity = IblockTable::compileEntity($code)) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_COMPILATION_ERROR', ['#API_CODE#' => $code]));
        }

        parent::__construct($entity);

        $this->properties = $this->getProperties();
    }

    /**
     * Получение ID инфоблока
     * @return mixed
     */
    public function getIblockId(): mixed
    {
        return $this->entity->getIblock()->getId();
    }

    /**
     * Получение информации об инфоблоке и его свойствах
     * @return array
     */
    public function getIblock(): array
    {
        $this->entity->getIblock()->fill();
        $fields = $this->entity->getIblock()->collectValues();
        $fields['PROPERTIES'] = $this->properties;

        return $fields;
    }

    /**
     * Получение списка элементов по параметрам
     *
     * @param array $params
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getElements(array $params): array
    {
        if (!isset($params['select'])) {
            $params['select'] = ['*'];
        }

        if (!empty($params['properties'])) {
            foreach ($params['properties'] as $propertyCode) {
                $propertyCode = strtoupper($propertyCode);
                if (isset($this->properties[$propertyCode]) && !in_array($propertyCode, $params['select'])) {
                    $params['select'][] = $propertyCode;
                }
            }
        }

        $this->setParams($params);

        if (isset($params['limit'])) {
            $this->setNavigation($params['limit'], $params['page'] ?? 1);
        }

        return $this->getResult();
    }

    protected function getResult(): array
    {
        $result = parent::getResult();

        foreach ($result as &$element) {
            $properties = [];
            foreach ($element['PROPERTIES'] as $code => $value) {
                $lowerCode = strtolower($code);
                $properties[$lowerCode] = IblockHelper::parseValue($this->properties[$code], $value);
            }
            $element['PROPERTIES'] = $properties;
        }
        unset($element);

        return $result;
    }

    protected function getOrder(): array
    {
        $sort = parent::getOrder();
        $sort[self::SORT_BY_DEFAULT] = self::SORT_ORDER_DEFAULT;

        return $sort;
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
                    $items = PropertyEnumerationTable::getList([
                        'filter' => [
                            'PROPERTY_ID' => $property['ID'],
                        ],
                        'cache' => ['ttl' => self::CACHE_TIME]
                    ])->fetchAll();
                    if ($items) {
                        $fields['ITEMS'] = $items;
                    }
                    break;
                default:

                    if (isset($fields['USER_TYPE']) && $fields['USER_TYPE'] === 'directory') {
                        $settings = unserialize($fields['USER_TYPE_SETTINGS']);
                        $table = $settings['TABLE_NAME'];

                        $hl = HighloadBlockTable::getList([
                            'filter' => ['TABLE_NAME' => $table],
                            'cache' => ['ttl' => self::CACHE_TIME]
                        ])->fetch();

                        if ($hl) {
                            $hlEntity = HighloadBlockTable::compileEntity($hl);
                            $entityDataClass = $hlEntity->getDataClass();

                            $items = $entityDataClass::getList()->fetchAll();

                            if ($items) {
                                $fields['ITEMS'] = $items;
                            }
                        }
                    }
            }

            $properties[$property->getCode()] = $fields;
            unset($properties[$key]);
        }

        return $properties;
    }

    /**
     * Получение свойства инфоблока
     *
     * @param string $name
     * @return array|null
     */
    public function getProperty(string $name): ?array
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Проверяет наличие поля инфоблока
     *
     * @param string $mame
     * @return bool
     */
    public function hasField(string $mame): bool
    {
        return $this->entity->hasField($mame);
    }


    /**
     * Инициализация репозитория по коду
     * @param string $code
     * @return self
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws RepositoryException
     * @throws SystemException
     */
    public static function factory(string $code): self
    {
        $iblock = IblockHelper::getIblockByCode($code, ['API_CODE']);

        if (!$iblock) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_NOT_FOUND_IBLOCK', ['#CODE#' => $code]));
        }
        
        if (empty($iblock['API_CODE'])) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_INVALID_API_CODE', ['#CODE#' => $code]));
        }

        return new self($iblock['API_CODE']);
    }

    /**
     * Добавление элемента в инфоблок
     *
     * @param array $fields основные поля элемента
     * @param array $properties значения свойств
     * @return int ID созданного элемента
     * @throws RepositoryException
     */
    public function addElement(array $fields, array $properties = []): int
    {
        $element = new \CIBlockElement();

        // Устанавливаем инфоблок по умолчанию из репозитория
        if (!isset($fields['IBLOCK_ID'])) {
            $fields['IBLOCK_ID'] = $this->getIblockId();
        }
        
        // Добавляем свойства если они переданы
        if (!empty($properties)) {
            $fields['PROPERTY_VALUES'] = $properties;
        }
        
        // Устанавливаем активность по умолчанию
        if (!isset($fields['ACTIVE'])) {
            $fields['ACTIVE'] = 'Y';
        }
        
        $elementId = $element->Add($fields);
        
        if (!$elementId) {
            throw new RepositoryException(
                Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_ELEMENT_ADD_ERROR') . 
                ': ' . $element->LAST_ERROR
            );
        }
        
        return (int)$elementId;
    }


    /**
     * Получение ID значения свойства типа список
     * 
     * @param string $propertyCode символьный код свойства
     * @param mixed $value искомое значение
     * @return int|null
     */
    public function getPropertyEnumId(string $propertyCode, ?string $value): ?int
    {
        if (!$value) {
            return null;
        }
        
        $propertyId = $this->getPropertyIdByCode($propertyCode);
        
        if (!$propertyId) {
            return null;
        }
        
        $result = PropertyEnumerationTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=PROPERTY_ID' => $propertyId,
                '=VALUE' => $value
            ],
            'limit' => 1
        ]);
        
        $enum = $result->fetch();
        
        return $enum ? (int)$enum['ID'] : null;
    }

    /**
     * Получение ID свойства по его коду
     *
     * @param string $code
     * @return int|null
     */
    private function getPropertyIdByCode(string $code): ?int
    {
        $property = PropertyTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=IBLOCK_ID' => $this->getIblockId(),
                '=CODE' => $code
            ],
            'limit' => 1
        ])->fetch();
        
        return $property ? (int)$property['ID'] : null;
    }
}