<?php

namespace Alto\MakeApi\Dto\Iblock\Property;

use Alto\MakeApi\Dto\BaseDto;

class TextDto extends BaseDto
{
    public readonly string $type;
    public readonly string $text;


    public function __construct(
        string $type,
        string $text
    )
    {
        $this->type = $type;
        $this->text = $text;
    }
}