<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Element;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use Alto\MakeApi\Dto\Entity\UserDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ElementDto",
    properties: [
        new OA\Property(
            property: "id",
            description: "ID элемента",
            type: "integer",
            example: 123
        ),
        new OA\Property(
            property: "code",
            description: "Символьный код",
            type: "string",
            nullable: true,
            example: "product-123"
        ),
        new OA\Property(
            property: "xml_id",
            description: "Внешний код",
            type: "string",
            nullable: true,
            example: "EXT_123"
        ),
        new OA\Property(
            property: "name",
            description: "Название элемента",
            type: "string",
            example: "Название товара"
        ),
        new OA\Property(
            property: "active",
            description: "Активность",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "date_create",
            description: "Дата создания",
            type: "string",
            nullable: true,
            example: "2023-01-15 10:30:00"
        ),
        new OA\Property(
            property: "date_update",
            description: "Дата обновления",
            type: "string",
            nullable: true,
            example: "2023-01-20 14:45:00"
        ),
        new OA\Property(
            property: "active_from",
            description: "Активен с",
            type: "string",
            nullable: true,
            example: "2023-01-01 00:00:00"
        ),
        new OA\Property(
            property: "active_to",
            description: "Активен до",
            type: "string",
            nullable: true,
            example: "2023-12-31 23:59:59"
        ),
        new OA\Property(
            property: "sort",
            description: "Сортировка",
            type: "integer",
            example: 500
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
            property: "section",
            description: "ID раздела",
            type: "integer",
            nullable: true,
            example: 5
        ),
        new OA\Property(
            property: "tags",
            description: "Теги",
            type: "string",
            example: "tag1, tag2, tag3"
        ),
        new OA\Property(
            property: "created_by",
            description: "Автор создания",
            ref: "#/components/schemas/UserDto"
        ),
        new OA\Property(
            property: "modified_by",
            description: "Автор изменения",
            ref: "#/components/schemas/UserDto"
        ),
        new OA\Property(
            property: "properties",
            description: "Свойства элемента",
            type: "array",
            nullable: true,
            items: new OA\Items(type: "object")
        ),
    ]
)]
class ElementDto extends BaseDto
{
    public readonly int $id;
    public readonly ?string $code;
    public readonly ?string $xml_id;
    public readonly string $name;
    public readonly bool $active;
    public readonly ?string $date_create;
    public readonly ?string $date_update;
    public readonly ?string $active_from;
    public readonly ?string $active_to;
    public readonly int $sort;
    public readonly TextDto $preview_text;
    public readonly TextDto $detail_text;
    public readonly ?PictureDto $preview_picture;
    public readonly ?PictureDto $detail_picture;
    public readonly ?int $section;
    public readonly string $tags;
    public readonly UserDto $created_by;
    public readonly UserDto $modified_by;
    public readonly ?array $properties;


    public function __construct(
        int $id,
        ?string $code,
        ?string $xmlId,
        string $name,
        bool $active,
        ?string $dateCreate,
        ?string $dateUpdate,
        ?string $activeFrom,
        ?string $activeTo,
        int $sort,
        TextDto $previewText,
        TextDto $detailText,
        ?PictureDto $previewPicture,
        ?PictureDto $detailPicture,
        ?int $section,
        string $tags,
        ?UserDto $createdBy,
        ?UserDto $modifiedBy,
        ?array $properties,

    ) {
        $this->id = $id;
        $this->code = $code;
        $this->xml_id = $xmlId;
        $this->name = $name;
        $this->active = $active;
        $this->date_create = $dateCreate;
        $this->date_update = $dateUpdate;
        $this->active_from = $activeFrom;
        $this->active_to = $activeTo;
        $this->sort = $sort;
        $this->preview_text = $previewText;
        $this->detail_text = $detailText;
        $this->preview_picture = $previewPicture;
        $this->detail_picture = $detailPicture;
        $this->section = $section;
        $this->tags = $tags;
        $this->created_by = $createdBy;
        $this->modified_by = $createdBy;
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
            $fields['XML_ID'],
            $fields['NAME'],
            ($fields['ACTIVE'] === 1),
            $fields['DATE_CREATE'],
            $fields['TIMESTAMP_X'],
            $fields['ACTIVE_FROM'],
            $fields['ACTIVE_TO'],
            $fields['SORT'],
            new TextDto($fields['PREVIEW_TEXT_TYPE'], $fields['PREVIEW_TEXT']),
            new TextDto($fields['DETAIL_TEXT_TYPE'], $fields['DETAIL_TEXT']),
            $fields['PREVIEW_PICTURE'],
            $fields['DETAIL_PICTURE'],
            $fields['IBLOCK_SECTION_ID'],
            $fields['TAGS'],
            $fields['CREATED_BY'],
            $fields['MODIFIED_BY'],
            $fields['PROPERTIES']
        );
    }
}