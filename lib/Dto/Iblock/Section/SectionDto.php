<?php

namespace Alto\MakeApi\Dto\Iblock\Section;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Iblock\PictureDto;
use Alto\MakeApi\Dto\Iblock\Property\TextDto;
use Alto\MakeApi\Dto\UserDto;

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
    public readonly string $date_create;
    public readonly string $date_update;
    public readonly ?PictureDto $picture;
    public readonly ?PictureDto $detail_picture;
    public readonly UserDto $created_by;
    public readonly UserDto $modified_by;

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
        string $date_create,
        string $date_update,
        ?PictureDto $picture,
        ?PictureDto $detailPicture,
        ?UserDto $created_by,
        ?UserDto $modified_by
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
        $this->date_create = $date_create;
        $this->date_update = $date_update;
        $this->picture = $picture;
        $this->detail_picture = $detailPicture;
        $this->created_by = $created_by;
        $this->modified_by = $modified_by;
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
            $fields['DATE_CREATE'],
            $fields['TIMESTAMP_X'],
            $fields['PICTURE'],
            $fields['DETAIL_PICTURE'],
            $fields['CREATED_BY'],
            $fields['MODIFIED_BY']
        );
    }
}