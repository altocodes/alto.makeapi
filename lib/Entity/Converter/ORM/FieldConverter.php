<?php

namespace Alto\MakeApi\Entity\Converter\ORM;

class FieldConverter
{
    /**
     * Префикс для идентификации свойств
     */
    const PREFIX_PROPERTY = 'PROPERTY_';
    /**
     * Получение кода свойства для select
     * @param string $code
     * @return string
     */
    public static function getPropertyCodeBySelect(string $code): string
    {
        if (mb_substr($code, 0, 9) == self::PREFIX_PROPERTY) {
            $code = mb_strtoupper(mb_substr($code, strlen(self::PREFIX_PROPERTY)));
        }

        return $code;
    }

    /**
     * Получение названия поля свойства в запросе
     * @param string $alias
     * @param string $code
     * @return string
     */
    public static function getPropertyValueCode(string $alias, string $code): string
    {
        if (mb_substr($code, 0, 9) == self::PREFIX_PROPERTY) {
            $code = strtoupper($alias) . '_' . mb_strtoupper(mb_substr($code, strlen(self::PREFIX_PROPERTY))) . '_VALUE';
        }

        return $code;
    }
}