<?php

namespace Alto\MakeApi\Dto\Entity;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MetaDto",
    properties: [
        new OA\Property(
            property: "title",
            description: "Заголовок страницы",
            type: "string",
            example: "Заголовок страницы"
        ),
        new OA\Property(
            property: "description",
            description: "Описание страницы",
            type: "string",
            example: "Описание страницы"
        ),
        new OA\Property(
            property: "robots",
            description: "Параметры для robots",
            type: "string",
            example: "index, follow"
        ),
        new OA\Property(
            property: "canonical",
            description: "Канонический URL страницы",
            type: "string",
        )
    ]
)]
class MetaDto extends BaseDto
{
    public readonly string $title;
    public readonly string $description;
    public readonly string $robots;
    public readonly string $canonical;

    public function __construct(string $title, string $description, string $robots, string $canonical)
    {
        $this->title = $title;
        $this->description = $description;
        $this->robots = $robots;
        $this->canonical = $canonical;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['TITLE'] ?? '',
            $fields['DESCRIPTION'] ?? '',
            $fields['ROBOTS'] ?? '',
            $fields['CANONICAL'] ?? '',
        );
    }
}