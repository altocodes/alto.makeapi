<?php

namespace Alto\MakeApi\Dto\Entity\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\Section\SectionDto;
use Alto\MakeApi\Dto\Entity\MetaDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SectionDetailDto",
    description: "Детальная информация о разделе",
    properties: [
        new OA\Property(
            property: "section",
            description: "Данные раздела",
            ref: "#/components/schemas/SectionDto"
        ),
        new OA\Property(
            property: "meta",
            description: "Мета-информация",
            ref: "#/components/schemas/MetaDto"
        )
    ]
)]
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