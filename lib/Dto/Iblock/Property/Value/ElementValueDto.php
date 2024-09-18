<?php

namespace Alto\MakeApi\Dto\Iblock\Property\Value;

use Alto\MakeApi\Dto\BaseDto;

class ElementValueDto extends BaseDto
{
    public readonly int $id;
    public readonly string $name;
    public readonly string $code;

    /**
     * @param int $id
     * @param string $name
     * @param string $code
     */
    public function __construct(
        int    $id,
        string $name,
        string $code,
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['NAME'],
            $fields['CODE']
        );
    }
}