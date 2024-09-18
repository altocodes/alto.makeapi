<?php

namespace Alto\MakeApi\Entity\Converter\ORM;

use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;

class ObjectifyConverter
{
    /**
     * Получение значений полей и свойств из коллекции
     * @param EntityObject $item
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getValues(EntityObject $item): array
    {
        $values = [];
        $properties = [];
        $collect = $item->collectValues(Values::ALL, FieldTypeMask::ALL, true);
        foreach($collect as $code => $value) {

            if (is_array($value)) {
                if (isset($value['VALUE'])) {
                    $value = $value['VALUE'];
                } elseif (count($value) > 0) {
                    $newValue = [];
                    foreach ($value as $v) {
                        if(isset($v['VALUE'])) {
                            $newValue[] = $v['VALUE'];
                        } else {
                            $newValue[] = $v;
                        }
                    }
                    $value = $newValue;
                }
            }

            // TODO: решение сомнительное, работает пока нет стандартных множественных свойств
            $prop = $item->get($code);
            if ($prop instanceof ValueStorage || $prop instanceof Collection) {
                $properties[$code] = $value;
            } else {
                $values[$code] = $value;
            }
        }

        if (count($properties) > 0) {
            $values['PROPERTIES'] = $properties;
        }

        return $values;
    }
}