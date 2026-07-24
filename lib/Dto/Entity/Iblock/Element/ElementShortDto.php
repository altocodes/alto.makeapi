<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Element;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use Alto\MakeApi\Helper\FetcherHelper;
use Alto\MakeApi\Service\Fetcher\ImageFetcher;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ElementShortDto",
    description: "Элемент инфоблока Bitrix в сокращенном виде",
    required: ["id", "name"],
    properties: [
        new OA\Property(
            property: "id",
            description: "Уникальный идентификатор элемента",
            type: "integer",
            example: 12345
        ),
        new OA\Property(
            property: "code",
            description: "Код элемента (символьный код)",
            type: "string",
            example: "product-123"
        ),
        new OA\Property(
            property: "name",
            description: "Название элемента",
            type: "string",
            example: "Название товара"
        ),
        new OA\Property(
            property: "preview_text",
            description: "Описание для анонса",
            ref: "#/components/schemas/TextDto"
        ),
        new OA\Property(
            property: "detail_text",
            description: "Детальное описание",
            ref: "#/components/schemas/TextDto"
        ),
        new OA\Property(
            property: "preview_picture",
            description: "Картинка для анонса",
            ref: "#/components/schemas/PictureDto",
            nullable: true
        ),
        new OA\Property(
            property: "detail_picture",
            description: "Детальная картинка",
            ref: "#/components/schemas/PictureDto",
            nullable: true
        ),
        new OA\Property(
            property: "tags",
            description: "Теги",
            type: "string",
            example: "тег 1, тег 2"
        ),
        new OA\Property(
            property: "properties",
            description: "Свойства",
            type: "object",
        ),
    ],
    type: "object"
)]
class ElementShortDto extends BaseDto
{
    public readonly int $id;
    public readonly ?string $code;
    public readonly string $name;
    public readonly ?PictureDto $preview_picture;
    public readonly ?PictureDto $detail_picture;

    public readonly ?TextDto $detail_text;
    public readonly ?TextDto $preview_text;

    public readonly ?string $tags;
    public readonly ?array $properties;

    public function __construct(
        int $id,
        ?string $code,
        string $name,
        ?PictureDto $preview_picture,
        ?PictureDto $detail_picture,
        ?TextDto $preview_text,
        ?TextDto $detail_text,
        ?string $tags,
        ?array $properties
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->preview_picture = $preview_picture;
        $this->detail_picture = $detail_picture;
        $this->preview_text = $preview_text;
        $this->detail_text = $detail_text;
        $this->tags = $tags;
        $this->properties = $properties;
    }

    /**
     * Инициализация из массива
     *
     * @param array $fields
     * @return self
     */
    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['CODE'],
            $fields['NAME'],
            $fields['PREVIEW_PICTURE'] ? FetcherHelper::getFileById((int)$fields['PREVIEW_PICTURE']) : null,
            $fields['DETAIL_PICTURE'] ? FetcherHelper::getFileById((int)$fields['DETAIL_PICTURE']) : null,
            new TextDto(
                $fields['PREVIEW_TEXT_TYPE'],
                $fields['PREVIEW_TEXT']
            ),
            new TextDto(
                $fields['DETAIL_TEXT_TYPE'],
                $fields['DETAIL_TEXT']
            ),
            $fields['TAGS'] ?? null,
            $fields['PROPERTIES'] ?? null
        );
    }
}