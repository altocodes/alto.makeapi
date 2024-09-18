<?php

namespace Alto\MakeApi\Dto\Iblock\Property;

use Alto\MakeApi\Dto\BaseDto;

class PropertyValueDto extends BaseDto
{
    public readonly int $id;
    public readonly string $name;
    public readonly string $code;
    public readonly string $type;
    public readonly mixed $value;

    public function __construct(
        int $id,
        string $name,
        string $code,
        string $type,
        mixed $value
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Инициализация из массива
     *
     * @param array $fields
     * @return self
     */
    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['NAME'],
            $fields['CODE'],
            $fields['PROPERTY_TYPE'],
            $fields['VALUE']
        );
    }
}