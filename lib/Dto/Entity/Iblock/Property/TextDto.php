<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Property;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TextDto",
    description: "Структурированное описание",
    required: ["type", "text"],
    properties: [
        new OA\Property(
            property: "type",
            description: "Тип описания",
            type: "string",
            example: "html"
        ),
        new OA\Property(
            property: "text",
            description: "Текст описания",
            type: "string",
            example: "Все виды электроники и бытовой техники"
        )
    ],
    type: "object"
)]
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