<?php

namespace Alto\MakeApi\Dto;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

class BaseDto implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        $data = [];

        $reflect = new ReflectionClass($this);

        $propertyList = $reflect->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_READONLY);

        foreach ($propertyList as $property) {
            $value = $reflect->getProperty($property->name)->getValue($this);
            if ($value !== null) {
                $data[$property->name] = $value;
            }
        }

        return $data;
    }
}