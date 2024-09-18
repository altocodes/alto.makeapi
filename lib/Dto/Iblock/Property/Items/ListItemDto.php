<?php

namespace Alto\MakeApi\Dto\Iblock\Property\Items;

use Alto\MakeApi\Dto\BaseDto;

class ListItemDto extends BaseDto
{
    public readonly int $id;
    public readonly string $value;
    public readonly bool $default;
    public readonly int $sort;
    public readonly string $xml_id;

    public function __construct(
        int    $id,
        string $value,
        bool $default,
        int $sort,
        string $xml_id,
    )
    {
        $this->id = $id;
        $this->value = $value;
        $this->default = $default;
        $this->sort = $sort;
        $this->xml_id = $xml_id;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['VALUE'],
            $fields['DEF'] === 'Y',
            $fields['SORT'],
            $fields['XML_ID'],
        );
    }
}