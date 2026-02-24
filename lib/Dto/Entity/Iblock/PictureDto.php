<?php

namespace Alto\MakeApi\Dto\Entity\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PictureDto",
    properties: [
        new OA\Property(
            property: "id",
            description: "ID",
            type: "int",
            example: 2
        ),
        new OA\Property(
            property: "url",
            description: "Список ссылок под устройства",
            properties: [
                new OA\Property(
                    property: "desktop",
                    description: "Ссылка для ПК",
                    type: "string",
                    example: "https://domain.ru/pic.jpg"
                ),
                new OA\Property(
                    property: "mobile",
                    description: "Ссылка для телефона",
                    type: "string",
                    example: "https://domain.ru/pic.jpg"
                ),
            ],
            type: "object"
        ),
        new OA\Property(
            property: "alt",
            description: "Alt-описание картинки",
            type: "string",
            example: "Первое изображение"
        ),
    ]
)]
class PictureDto extends BaseDto
{
    public readonly  int $id;
    public readonly  array $url;
    public readonly  string $alt;
    public readonly string $type;

    public function __construct(
        int $id,
        array $url,
        string $alt,
    )
    {
        $this->id = $id;
        $this->url = $url;
        $this->alt = $alt;
        $this->type = 'image';
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['URL'],
            $fields['ALT'] ?? null
        );
    }
}