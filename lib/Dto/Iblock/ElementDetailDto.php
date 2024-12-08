<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Iblock\Element\ElementDto;
use Alto\MakeApi\Dto\MetaDto;

class ElementDetailDto extends BaseDto
{
    public readonly ElementDto $element;
    public readonly MetaDto $meta;

    public function __construct(
        ElementDto $element,
        MetaDto $meta
    ) {
        $this->element = $element;
        $this->meta = $meta;
    }
}