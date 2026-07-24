<?php

namespace Alto\MakeApi\Dto\Entity;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "BreadcrumbDto",
    properties: [
        new OA\Property(
            property: "title",
            description: "Название крошки",
            type: "string",
            example: "Категория №1"
        ),
        new OA\Property(
            property: "code",
            description: "Символьный код",
            type: "string",
            example: "category-1"
        ),
        new OA\Property(
            property: "type",
            description: "Параметры для robots",
            type: "string",
            enum: ['page', 'section', 'element'],
        )
    ]
)]
class BreadcrumbDto extends BaseDto
{
    public readonly string $title;
    public readonly string $code;
    public readonly string $type;

    public function __construct(string $title, string $code, string $type)
    {
        $this->title = $title;
        $this->code = $code;
        $this->type = $type;
    }
}