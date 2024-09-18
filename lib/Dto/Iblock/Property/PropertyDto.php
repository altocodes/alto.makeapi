<?php

namespace Alto\MakeApi\Dto\Iblock\Property;

use Alto\MakeApi\Dto\BaseDto;

class PropertyDto extends BaseDto
{
    public readonly int $id;
    public readonly string $name;
    public readonly string $code;
    public readonly bool $active;
    public readonly bool $require;
    public readonly bool $multiple;
    public readonly string $type;
    public readonly ?array $items;

    /**
     * @param int $id
     * @param string $name
     * @param string $code
     * @param bool $active
     * @param bool $require
     * @param bool $multiple
     * @param string $type
     * @param ?array $items
     */
    public function __construct(
        int    $id,
        string $name,
        string $code,
        bool   $active,
        bool   $require,
        bool   $multiple,
        string $type,
        ?array $items,
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->active = $active;
        $this->require = $require;
        $this->multiple = $multiple;
        $this->type = $type;
        $this->items = $items;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['NAME'],
            $fields['CODE'],
            $fields['ACTIVE'],
            $fields['IS_REQUIRED'],
            $fields['MULTIPLE'],
            $fields['PROPERTY_TYPE'],
            $fields['ITEMS'] ?? null,
        );
    }
}