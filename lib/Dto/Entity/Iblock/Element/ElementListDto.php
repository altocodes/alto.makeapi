<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Element;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use Alto\MakeApi\Dto\Entity\Iblock\Section\SectionListDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ElementListDto",
    description: "Элемент инфоблока Bitrix с полными данными и связями",
    required: ["id", "code", "name", "url", "active", "sort"],
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
            property: "url",
            description: "URL элемента",
            type: "string",
            format: "uri",
            example: "https://site.ru/catalog/product-123/"
        ),
        new OA\Property(
            property: "active",
            description: "Флаг активности элемента",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "date_create",
            description: "Дата создания элемента",
            type: "string",
            format: "date-time",
            example: "2023-01-01T10:00:00+00:00",
            nullable: true
        ),
        new OA\Property(
            property: "active_from",
            description: "Дата начала активности элемента",
            type: "string",
            format: "date-time",
            example: "2023-01-01T00:00:00+00:00",
            nullable: true
        ),
        new OA\Property(
            property: "active_to",
            description: "Дата окончания активности элемента",
            type: "string",
            format: "date-time",
            example: "2023-12-31T23:59:59+00:00",
            nullable: true
        ),
        new OA\Property(
            property: "sort",
            description: "Порядок сортировки элемента",
            type: "integer",
            example: 500
        ),
        new OA\Property(
            property: "preview_text",
            ref: "#/components/schemas/TextDto",
            description: "Текст превью элемента",
            nullable: true
        ),
        new OA\Property(
            property: "detail_text",
            ref: "#/components/schemas/TextDto",
            description: "Детальный текст элемента",
            nullable: true
        ),
        new OA\Property(
            property: "preview_picture",
            ref: "#/components/schemas/PictureDto",
            description: "Превью изображение элемента"
        ),
        new OA\Property(
            property: "detail_picture",
            ref: "#/components/schemas/PictureDto",
            description: "Детальное изображение элемента"
        ),
        new OA\Property(
            property: "section",
            ref: "#/components/schemas/SectionDto",
            description: "Раздел, к которому относится элемент"
        ),
        new OA\Property(
            property: "tags",
            description: "Теги элемента (через запятую)",
            type: "string",
            example: "акция, скидка, новинка",
            nullable: true
        ),
        new OA\Property(
            property: "properties",
            description: "Свойства элемента",
            type: "array",
            items: new OA\Items()
        )
    ],
    type: "object"
)]
class ElementListDto extends BaseDto
{
    public readonly int $id;
    public readonly ?string $code;
    public readonly string $name;
    public readonly bool $active;
    public readonly ?string $date_create;
    public readonly ?string $active_from;
    public readonly ?string $active_to;
    public readonly int $sort;
    public readonly TextDto $preview_text;
    public readonly TextDto $detail_text;
    public readonly ?PictureDto $preview_picture;
    public readonly ?PictureDto $detail_picture;
    public readonly ?SectionListDto $section;
    public readonly ?array $properties;
    public readonly string $tags;

    public function __construct(
        int $id,
        ?string $code,
        string $name,
        bool $active,
        ?string $dateCreate,
        ?string $activeFrom,
        ?string $activeTo,
        int $sort,
        TextDto $previewText,
        TextDto $detailText,
        ?PictureDto $previewPicture,
        ?PictureDto $detailPicture,
        ?SectionListDto $section,
        string $tags,
        ?array $properties,

    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->active = $active;
        $this->date_create = $dateCreate;
        $this->active_from = $activeFrom;
        $this->active_to = $activeTo;
        $this->sort = $sort;
        $this->preview_text = $previewText;
        $this->detail_text = $detailText;
        $this->preview_picture = $previewPicture;
        $this->detail_picture = $detailPicture;
        $this->section = $section;
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
            $fields['ACTIVE'],
            $fields['DATE_CREATE'],
            $fields['ACTIVE_FROM'],
            $fields['ACTIVE_TO'],
            $fields['SORT'],
            new TextDto($fields['PREVIEW_TEXT_TYPE'], $fields['PREVIEW_TEXT']),
            new TextDto($fields['DETAIL_TEXT_TYPE'], $fields['DETAIL_TEXT']),
            $fields['PREVIEW_PICTURE'],
            $fields['DETAIL_PICTURE'],
            $fields['IBLOCK_SECTION_ID'],
            $fields['TAGS'],
            $fields['PROPERTIES']
        );
    }
}