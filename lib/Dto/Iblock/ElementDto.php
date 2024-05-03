<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;

class ElementDto extends BaseDto
{
    private int $id;
    private ?string $code;
    private string $name;
    private string $url;
    private bool $active;
    private ?string $dateCreate;
    private ?string $activeFrom;
    private ?string $activeTo;
    private int $sort;
    private string $previewText;
    private string $detailText;
    private ?PictureDto $previewPicture;
    private ?PictureDto $detailPicture;
    private ?SectionDto $section;
    private ?array $propertyValues;
    private string $tags;

    public function __construct(
        int $id,
        ?string $code,
        string $name,
        string $url,
        bool $active,
        ?string $dateCreate,
        ?string $activeFrom,
        ?string $activeTo,
        int $sort,
        string $previewText,
        string $detailText,
        ?PictureDto $previewPicture,
        ?PictureDto $detailPicture,
        ?SectionDto $section,
        string $tags,
        ?array $propertyValues,

    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->url = $url;
        $this->active = $active;
        $this->dateCreate = $dateCreate;
        $this->activeFrom = $activeFrom;
        $this->activeTo = $activeTo;
        $this->sort = $sort;
        $this->previewText = $previewText;
        $this->detailText = $detailText;
        $this->previewPicture = $previewPicture;
        $this->detailPicture = $detailPicture;
        $this->section = $section;
        $this->tags = $tags;
        $this->propertyValues = $propertyValues;

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
            $fields['DETAIL_PAGE_URL'],
            ($fields['ACTIVE'] === 1),
            $fields['DATE_CREATE'],
            $fields['ACTIVE_FROM'],
            $fields['ACTIVE_TO'],
            $fields['SORT'],
            $fields['PREVIEW_TEXT'],
            $fields['DETAIL_TEXT'],
            PictureDto::byId($fields['PREVIEW_PICTURE']),
            PictureDto::byId($fields['DETAIL_PICTURE']),
            SectionDto::byId($fields['IBLOCK_SECTION_ID']),
            $fields['TAGS'],
            $fields['PROPERTY_VALUES']
        );
    }
}