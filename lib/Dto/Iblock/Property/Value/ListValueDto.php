<?php

namespace Alto\MakeApi\Dto\Iblock\Property\Value;

use Alto\MakeApi\Dto\BaseDto;

class ListValueDto extends BaseDto
{
    public readonly int $id;
    public readonly string $value;
    public readonly string $xml_id;

    /**
     * @param int $id
     * @param string $value
     * @param string $xml_id
     */
    public function __construct(
        int    $id,
        string $value,
        string $xml_id,
    )
    {
        $this->id = $id;
        $this->value = $value;
        $this->xml_id = $xml_id;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['VALUE'],
            $fields['XML_ID']
        );
    }
}