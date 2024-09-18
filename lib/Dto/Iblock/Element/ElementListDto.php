<?php

namespace Alto\MakeApi\Dto\Iblock\Element;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Iblock\PictureDto;
use Alto\MakeApi\Dto\Iblock\Property\TextDto;
use Alto\MakeApi\Dto\Iblock\Section\SectionListDto;

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