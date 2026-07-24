<?php

namespace Alto\MakeApi\Dto\Entity;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MenuDto",
    properties: [
        new OA\Property(
            property: "title",
            description: "Название ссылки",
            type: "string",
            example: "Каталог"
        ),
        new OA\Property(
            property: "url",
            description: "URL-адрес",
            type: "string",
            example: "/catalog"
        ),
        new OA\Property(
            property: "params",
            description: "Параметры пункта; после ответа API ключ children может быть массивом вложенных пунктов вместо строки-директивы",
            type: "array",
            items: new OA\Items()
        ),
    ]
)]
class MenuDto extends BaseDto
{
    public readonly string $title;
    public readonly string $url;
    public readonly array $params;

    public function __construct(string $title, string $url, array $params)
    {
        $this->title = $title;
        $this->url = $url;
        $this->params = $params;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields[0] ?? '',
            $fields[1] ?? '',
            $fields[3] ?? []
        );
    }
}
