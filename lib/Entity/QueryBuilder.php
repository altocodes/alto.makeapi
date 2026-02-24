<?php

namespace Alto\MakeApi\Entity;

use Alto\MakeApi\Entity\Converter\ORM\FieldConverter;
use Alto\MakeApi\Entity\Converter\ORM\FilterConverter;
use Alto\MakeApi\Entity\Converter\ORM\ObjectifyConverter;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\UI\PageNavigation;

/**
 * Класс для построения запросов от сущности
 */
class QueryBuilder
{
    protected Entity $entity;
    private array $params = []; // Инициализируем пустым массивом
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
     * - runtime - runtime поля
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
     * @param int $page
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setNavigation(int $limit = 10, int $page = 1)
    {
        $this->navigation  = new PageNavigation(get_class($this->entity));
        $this->navigation->allowAllRecords(true)
            ->setPageSize($limit)
            ->setCurrentPage($page)
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

        // Регистрируем runtime поля
        if (isset($this->params['runtime'])) {
            foreach ($this->params['runtime'] as $fieldName => $fieldDefinition) {
                if ($fieldDefinition instanceof \Bitrix\Main\ORM\Fields\Field) {
                    // Если это Field объект
                    $this->query->registerRuntimeField($fieldDefinition);
                } else {
                    // Если это массив с определением
                    $this->query->registerRuntimeField($fieldName, $fieldDefinition);
                }
            }
        }

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
     * @throws \Bitrix\Main\SystemException
     */
    protected function getSelect(): array
    {
        $select = ['*'];

        if (isset($this->params['select']) && $this->params['select']) {
            $select = [];
            foreach ($this->params['select'] as $alias => $code) {
                // Обрабатываем как числовые ключи, так и строковые
                if (is_numeric($alias)) {
                    $select[] = FieldConverter::getPropertyCodeBySelect($code);
                } else {
                    $select[$alias] = FieldConverter::getPropertyCodeBySelect($code);
                }
            }
        }

        // Добавляем ID если его нет
        if (!in_array('ID', $select)) {
            $select[] = 'ID';
        }

        // Добавляем свойства инфоблока из фильтров
        if (isset($this->params['filter']) && is_array($this->params['filter'])) {
            foreach ($this->params['filter'] as $key => $value) {
                // Проверяем, начинается ли ключ фильтра с PROPERTY_
                if (strpos($key, 'PROPERTY_') === 0) {
                    // Используем FieldConverter для получения кода свойства
                    $property = FieldConverter::getPropertyCodeBySelect($key);
                    
                    // Проверяем, что свойства еще нет в SELECT
                    if (!in_array($property, $select, true) && !array_key_exists($property, $select)) {
                        $select[] = $property;
                    }
                }
            }
        }

        return $select;
    }

    /**
     * Получение правильной структуры ORDER
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getOrder(): array
    {
        $sort = [];
        if (isset($this->params['sort'])) {
            foreach ($this->params['sort'] as $by => $order) {
                if (isset($this->query)) {
                    $by = FieldConverter::getPropertyValueCode($this->query->getInitAlias(), $by);
                }
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
            if (isset($this->query)) {
                $filters = FilterConverter::getFilter($this->query->getInitAlias(), $this->params['filter'], $this->properties);
            } else {
                // Fallback если query еще не создан
                $filters = $this->params['filter'];
            }
        }

        return $filters;
    }
}