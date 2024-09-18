<?php

namespace Alto\MakeApi\Dto\Iblock\Section;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Iblock\PictureDto;

class SectionListDto extends BaseDto
{
    public readonly int $id;
    public readonly ?string $xml_id;
    public readonly string $name;
    public readonly ?string $code;
    public readonly bool $active;
    public readonly string $sort;
    public readonly int $parent_id;
    public readonly int $depth_level;
    public readonly ?PictureDto $picture;

    public function __construct(
        int $id,
        ?string $xml_id,
        string $name,
        ?string $code,
        bool $active,
        string $sort,
        int $parent_id,
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
            $fields['ID'],
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