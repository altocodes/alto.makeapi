<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Section;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SectionDto",
    description: "Раздел (секция) инфоблока Bitrix",
    required: ["id", "name", "code", "url", "active", "sort"],
    properties: [
        new OA\Property(
            property: "id",
            description: "Уникальный идентификатор раздела",
            type: "integer",
            example: 10
        ),
        new OA\Property(
            property: "name",
            description: "Название раздела",
            type: "string",
            example: "Электроника"
        ),
        new OA\Property(
            property: "description",
            ref: "#/components/schemas/TextDto",
            description: "Описание раздела"
        ),
        new OA\Property(
            property: "code",
            description: "Код раздела (символьный код)",
            type: "string",
            example: "electronics"
        ),
        new OA\Property(
            property: "url",
            description: "URL раздела",
            type: "string",
            format: "uri",
            example: "https://site.ru/catalog/electronics/"
        ),
        new OA\Property(
            property: "active",
            description: "Флаг активности раздела",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "sort",
            description: "Порядок сортировки раздела",
            type: "integer",
            example: 100
        ),
        new OA\Property(
            property: "picture",
            ref: "#/components/schemas/PictureDto",
            description: "Изображение раздела",
            nullable: true
        ),
        new OA\Property(
            property: "detail_picture",
            ref: "#/components/schemas/PictureDto",
            description: "Детальное изображение раздела",
            nullable: true
        )
    ],
    type: "object"
)]
class SectionDto extends BaseDto
{
    public readonly int $id;
    public readonly string $xml_id;
    public readonly string $name;
    public readonly TextDto $description;
    public readonly ?string $code;
    public readonly bool $active;
    public readonly string $sort;
    public readonly int $parent_id;
    public readonly int $depth_level;
    public readonly ?PictureDto $picture;
    public readonly ?PictureDto $detail_picture;

    public function __construct(
        int $id,
        string $xml_id,
        string $name,
        TextDto $description,
        ?string $code,
        bool $active,
        string $sort,
        int $parent_id,
        int $depth_level,
        ?PictureDto $picture,
        ?PictureDto $detailPicture
    )
    {
        $this->id = $id;
        $this->xml_id = $xml_id;
        $this->name = $name;
        $this->description = $description;
        $this->code = $code;
        $this->active = $active;
        $this->sort = $sort;
        $this->parent_id = $parent_id;
        $this->depth_level = $depth_level;
        $this->picture = $picture;
        $this->detail_picture = $detailPicture;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['XML_ID'],
            $fields['NAME'],
            new TextDto($fields['DESCRIPTION_TYPE'], $fields['DESCRIPTION']),
            $fields['CODE'],
            $fields['ACTIVE'],
            $fields['SORT'],
            $fields['IBLOCK_SECTION_ID'],
            $fields['DEPTH_LEVEL'],
            $fields['PICTURE'],
            $fields['DETAIL_PICTURE']
        );
    }
}