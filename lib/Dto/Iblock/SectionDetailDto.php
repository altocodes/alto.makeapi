<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Iblock\Element\ElementDto;
use Alto\MakeApi\Dto\Iblock\Section\SectionDto;
use Alto\MakeApi\Dto\MetaDto;

class SectionDetailDto extends BaseDto
{
    public readonly SectionDto $section;
    public readonly MetaDto $meta;

    public function __construct(
        SectionDto $section,
        MetaDto $meta
    ) {
        $this->section = $section;
        $this->meta = $meta;
    }
}