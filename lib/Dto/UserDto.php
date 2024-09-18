<?php

namespace Alto\MakeApi\Dto;

class UserDto extends BaseDto
{
    public readonly int $id;
    public readonly bool $active;
    public readonly string $name;
    public readonly ?string $second_name;
    public readonly ?string $last_name;
    public readonly ?string $xml_id;

    public function __construct(
        int $id,
        bool $active,
        string $name,
        ?string $second_name,
        ?string $last_name,
        ?string $xml_id
    ) {
        $this->id = $id;
        $this->active = $active;
        $this->name = $name;
        $this->second_name = $second_name;
        $this->last_name = $last_name;
        $this->xml_id = $xml_id;
    }

    public static function fromArray(array $fields)
    {
        return new self(
            (int) $fields['ID'],
            $fields['ACTIVE'] === 'Y',
            $fields['NAME'],
            $fields['SECOND_NAME'],
            $fields['LAST_NAME'],
            $fields['XML_ID'],
        );
    }
}