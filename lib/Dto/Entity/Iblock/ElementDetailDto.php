<?php

namespace Alto\MakeApi\Dto\Entity\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\Element\ElementDto;
use Alto\MakeApi\Dto\Entity\MetaDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ElementDetailDto",
    properties: [
        new OA\Property(
            property: "element",
            description: "Элемент инфоблока",
            ref: "#/components/schemas/ElementDto"
        ),
        new OA\Property(
            property: "meta",
            description: "Мета-информация",
            ref: "#/components/schemas/MetaDto"
        ),
    ]
)]
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