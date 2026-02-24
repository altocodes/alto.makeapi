<?php

namespace Alto\MakeApi\Entity\Converter\ORM;

// TODO:: нормальная поддержка значений фильтра XML_ID для справочников и Привязка к элементу ИБ
use Bitrix\Iblock\PropertyTable;

class FilterConverter
{
    /**
     * Получение правильного фильтра для Query
     * TODO: добавить обработку комбинированных фильтров (и, или)
     * @param string $alias
     * @param array $filter
     * @param array $properties
     * @return array
     */
    public static function getFilter(string $alias, array $filter, array $properties): array
    {
        $filters = [];

        foreach($filter as $prop => $value) {
            if (is_array($value) && isset($value['LOGIC'])) {
                $values = [];
                $logic = false;
                foreach ($value as $key => $item) {
                    if(is_array($item)) {

                        $code = array_key_first($item);
                        $v = $item[$code];
                        [$definition, $operation] = array_values(self::getCSWResult($code));

                        $propCode = (str_starts_with($definition, FieldConverter::PREFIX_PROPERTY) ? substr($definition, 9) : null);
                        if ($propCode !== null && isset($properties[$propCode])) {
                            $value = self::getCurrentValue($properties, $propCode, $value);
                        }

                        $definition = FieldConverter::getPropertyValueCode($alias, $definition);
                        $item = [$operation . $definition => $v];

                    } else {
                        if($key === 'LOGIC') {
                            $logic = $item;
                        }
                    }

                    $values[$key] = $item;
                }

                if(count($values) > 0 && $logic) {
                    $values['LOGIC'] = $logic;
                    $filters[] = $values;
                }

            } else {
                [$definition, $operation] = array_values(self::getCSWResult($prop));

                $propCode = (str_starts_with($definition, FieldConverter::PREFIX_PROPERTY) ? substr($definition, 9) : null);
                if ($propCode !== null && isset($properties[$propCode])) {
                    $value = self::getCurrentValue($properties, $propCode, $value);
                }

                // Поле TAGS является строкой, поэтому добавляем нечеткий поиск только по нему
                if (strtoupper($definition) === 'TAGS') {
                    // Для TAGS делаем поиск через LIKE
                    $definition = FieldConverter::getPropertyValueCode($alias, $definition);
                    $filters[$definition] = '%' . $value . '%';
                } else {
                    // Для остальных полей - обычная обработка
                    $definition = FieldConverter::getPropertyValueCode($alias, $definition);
                    $filters[$operation . $definition] = $value;
                }
            }
        }

        return $filters;
    }

    /**
     * Получение правильного значения, по XML_ID, для справочника и элементов
     * @param array $properties
     * @param string $code
     * @param $value
     * @return mixed
     */
    protected static function getCurrentValue(array $properties, string $code, $value)
    {
        if (isset($properties[$code])) {
            $prop = $properties[$code];
            switch ($prop['PROPERTY_TYPE']) {
                case PropertyTable::TYPE_LIST:
                    if ($items = $prop['ITEMS'] ?? false) {
                        foreach ($items as $item) {
                            if (isset($item['XML_ID']) && (string)$item['XML_ID'] === (string)$value) {
                                $value = $item['ID'];
                                break;
                            }
                        }
                    }
                    break;
                case PropertyTable::TYPE_ELEMENT:
                    // TODO: реализовать так же для получения ID элемента
                    break;
            }
        }

        return $value;
    }

    /**
     * Определение кода свойства и оператора
     * @param string $code
     * @return array
     */
    protected static function getCSWResult(string $code) : array
    {
        $sqlWhere = new \CSQLWhere();
        $csw_result = $sqlWhere->makeOperation($code);
        [$definition, $operation] = array_values($csw_result);
        $operation = \CSQLWhere::getOperationByCode($operation);

        if(!isset($operation)) {
            $operation = '';
        }

        return [$definition, $operation];
    }
}