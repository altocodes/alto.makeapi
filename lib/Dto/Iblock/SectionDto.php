<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use Bitrix\Iblock\SectionTable;

class SectionDto extends BaseDto
{
    private int $id;
    private string $name;
    private string $description;
    private ?string $code;
    private ?string $url;
    private string $active;
    private string $sort;
    private ?PictureDto $picture;
    private ?PictureDto $detailPicture;

    public function __construct(
        int $id,
        string $name,
        string $description,
        ?string $code,
        ?string $url,
        string $active,
        string $sort,
        ?PictureDto $picture,
        ?PictureDto $detailPicture
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->code = $code;
        $this->url = $url;
        $this->active = $active;
        $this->sort = $sort;
        $this->picture = $picture;
        $this->detailPicture = $detailPicture;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['NAME'],
            $fields['DESCRIPTION'],
            $fields['CODE'],
            \CIBlock::ReplaceSectionUrl($fields['SECTION_PAGE_URL'], $fields, false, 'S'),
            $fields['ACTIVE'],
            $fields['SORT'],
            PictureDto::byId($fields['PICTURE']),
            PictureDto::byId($fields['DETAIL_PICTURE'])
        );
    }

    public static function byId(?int $id): ?self
    {
        // TODO: убрать запрос
        $section = SectionTable::getList([
            'select' => ['ID', 'NAME', 'DESCRIPTION', 'CODE', 'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL', 'ACTIVE', 'SORT', 'PICTURE', 'DETAIL_PICTURE'],
            'filter' => ['ID' => $id]
        ])->fetch();
        if (!$section) {
            return null;
        }

        return self::fromArray($section);
    }
}