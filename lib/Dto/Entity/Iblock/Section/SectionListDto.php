<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Section;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SectionListDto",
    description: "Раздел для списка",
    properties: [
        new OA\Property(
            property: "id",
            description: "ID раздела",
            type: "integer",
            example: 10
        ),
        new OA\Property(
            property: "xml_id",
            description: "Внешний код",
            type: "string",
            nullable: true,
            example: "xml_section_123"
        ),
        new OA\Property(
            property: "name",
            description: "Название раздела",
            type: "string",
            example: "Электроника"
        ),
        new OA\Property(
            property: "code",
            description: "Символьный код",
            type: "string",
            nullable: true,
            example: "electronics"
        ),
        new OA\Property(
            property: "active",
            description: "Активность",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "sort",
            description: "Сортировка",
            type: "string",
            example: "100"
        ),
        new OA\Property(
            property: "parent_id",
            description: "ID родительского раздела",
            type: "integer",
            example: 0
        ),
        new OA\Property(
            property: "depth_level",
            description: "Уровень вложенности",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "picture",
            description: "Изображение",
            ref: "#/components/schemas/PictureDto",
            nullable: true
        )
    ]
)]
class SectionListDto extends BaseDto
{
    public readonly int $id;
    public readonly ?string $xml_id;
    public readonly string $name;
    public readonly ?string $code;
    public readonly bool $active;
    public readonly string $sort;
    public readonly ?int $parent_id;
    public readonly int $depth_level;
    public readonly ?PictureDto $picture;

    public function __construct(
        int $id,
        ?string $xml_id,
        string $name,
        ?string $code,
        bool $active,
        string $sort,
        ?int $parent_id,
        int $depth_level,
        ?PictureDto $picture
    )
    {
        $this->id = $id;
        $this->xml_id = $xml_id;
        $this->name = $name;
        $this->code = $code;
        $this->active = $active;
        $this->sort = $sort;
        $this->parent_id = $parent_id;
        $this->depth_level = $depth_level;
        $this->picture = $picture;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            (int)$fields['ID'],
            $fields['XML_ID'],
            $fields['NAME'],
            $fields['CODE'],
            $fields['ACTIVE'],
            $fields['SORT'],
            $fields['IBLOCK_SECTION_ID'],
            $fields['DEPTH_LEVEL'],
            $fields['PICTURE'],
        );
    }
}