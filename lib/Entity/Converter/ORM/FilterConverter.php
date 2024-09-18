<?php

namespace Alto\MakeApi\Entity\Converter\ORM;

class FilterConverter
{
    /**
     * Получение правильного фильтра для Query
     * TODO: добавить обработку комбинированных фильтров (и, или)
     * @param string $alias
     * @param array $filter
     * @return array
     */
    public static function getFilter(string $alias, array $filter): array
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

                $definition = FieldConverter::getPropertyValueCode($alias, $definition);
                $filters[$operation . $definition] = $value;

            }
        }

        return $filters;
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