<?php

namespace Alto\MakeApi\Entity;

use Alto\MakeApi\Entity\Converter\ORM\FieldConverter;
use Alto\MakeApi\Entity\Converter\ORM\FilterConverter;
use Alto\MakeApi\Entity\Converter\ORM\ObjectifyConverter;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\UI\PageNavigation;

/**
 * Класс для построения запросов от сущности
 */
class QueryBuilder
{

    protected Entity $entity;
    private array $params;
    private Query $query;
    private PageNavigation $navigation;

    /**
     * @param Entity $entity - объект Entity, полученный например для инфоблока через IblockTable::compileEntity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * Установка параметров для запроса
     * @param array $params включает в себя:
     * - select - какие поля выбрать, по умолчанию *
     * - filter - фильтр запроса (where)
     * - sort - параметры сортировки [ID => asc]
     * @return void
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Получение результата запроса
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getResult(): array
    {
        $arData = [];
        $collection = $this->getQuery()->fetchCollection()->getAll();

        foreach ($collection as $item) {
            $arData[] = ObjectifyConverter::getValues($item);
        }

        return $arData;
    }

    /**
     * Установка параметров постраничной навигации
     * @param int $limit
     * @param int $offset
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setNavigation(int $limit = 10, int $offset = 1)
    {
        $this->navigation  = new PageNavigation(get_class($this->entity));
        $this->navigation->allowAllRecords(true)
            ->setPageSize($limit)
            ->setCurrentPage($offset)
            ->initFromUri();
        $this->navigation->setRecordCount($this->getCount());
    }

    /**
     * Получение объекта постраничной навигации
     * @return PageNavigation
     */
    public function getNavigation(): PageNavigation
    {
        return $this->navigation;
    }

    /**
     * Получение кол-ва записей
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCount() : int
    {
        return $this->getQuery(true)->fetchCollection()->count();
    }

    /**
     * Формирование запроса на выборку
     * @param bool $count - true, если нужно посчитать общее кол-во записей
     * @return Query
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getQuery(bool $count = false): Query
    {
        $this->query = new Query($this->entity);

        $this->query->setSelect($this->getSelect());
        $this->query->setOrder($this->getOrder());
        $this->query->setFilter($this->getFilter());

        if (!$count) {
            if (isset($this->navigation) && $this->navigation instanceof PageNavigation) {
                $ids = [];
                $query = clone $this->query;
                $key = $this->entity->getPrimary();
                $result = $query->exec()->fetchAll();

                foreach ($result as $item) {
                    if (!in_array($item[$key], $ids))
                        $ids[] = $item[$key];
                }

                $ids = array_slice($ids, $this->navigation->getOffset(), $this->navigation->getLimit());
                $this->query->addFilter($this->entity->getPrimary(), $ids);
            }
        }

        return $this->query;
    }

    /**
     * Получение правильной структуры SELECT
     * @return array|string[]
     */
    protected function getSelect(): array
    {
        $select = ['*'];

        if ($this->params['select']) {
            $select = [];
            foreach ($this->params['select'] as $alias => $code) {
                $select[$alias] = FieldConverter::getPropertyCodeBySelect($code);
            }
        }

        if (!isset($select['ID']) || !in_array('ID', $select)) {
            $select[] = 'ID';
        }

        return $select;
    }

    /**
     * Получение правильной структуры ORDER
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getOrder(): array
    {
        $sort = [];
        if (isset($this->params['sort'])) {
            foreach ($this->params['sort'] as $by => $order) {
                $by = FieldConverter::getPropertyValueCode($this->query->getInitAlias(), $by);
                $sort[$by] = $order;
            }
        }

        return $sort;
    }

    /**
     * Получение правильной структуры фильтров
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    protected function getFilter(): array
    {
        $filters = [];

        if (isset($this->params['filter'])) {
            $filters = FilterConverter::getFilter($this->query->getInitAlias(), $this->params['filter']);
        }

        return $filters;
    }
}