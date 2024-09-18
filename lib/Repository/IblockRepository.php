<?php

namespace Alto\MakeApi\Repository;


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
     * TODO: не выбирать все поля, а только нужные
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
            foreach (array_keys($this->properties) as $key) {
                $params['select'][] = 'PROPERTY_' . $key;
            }
        }

        $this->setParams($params);

        if ($params['limit']) {
            $this->setNavigation($params['limit'], $params['offset'] ?? 1);
        }

        return $this->getResult();
    }

    protected function getResult(): array
    {
        $result = parent::getResult();

        foreach ($result as &$element) {
            foreach ($element['PROPERTIES'] as $code => $value) {
                $element['PROPERTIES'][$code] = IblockHelper::parseValue($this->properties[$code], $value);
            }
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

}