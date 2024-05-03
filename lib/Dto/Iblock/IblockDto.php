<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;

class IblockDto extends BaseDto
{
    private int $id;
    private string $apiCode;
    private string $version;
    private string $timestamp;
    private string $iblockTypeId;
    private string $siteId;
    private string $code;
    private string $name;
    private bool $active;
    private int $sort;
    private string $listPageUrl;
    private string $detailPageUrl;
    private string $sectionPageUrl;
    private string $canonicalPageUrl;
    private ?PictureDto $picture;
    private string $description;
    private ?array $properties;

    public function __construct(
        int $id,
        string $apiCode,
        string $version,
        string $timestamp,
        string $iblockTypeId,
        string $siteId,
        string $code,
        string $name,
        bool $active,
        int $sort,
        string $listPageUrl,
        string $detailPageUrl,
        string $sectionPageUrl,
        string $canonicalPageUrl,
        ?PictureDto $picture,
        string $description,
        ?array $properties
    ) {
        $this->id = $id;
        $this->apiCode = $apiCode;
        $this->version = $version;
        $this->timestamp = $timestamp;
        $this->iblockTypeId = $iblockTypeId;
        $this->siteId = $siteId;
        $this->code = $code;
        $this->name = $name;
        $this->active = $active;
        $this->sort = $sort;
        $this->listPageUrl = $listPageUrl;
        $this->detailPageUrl = $detailPageUrl;
        $this->sectionPageUrl = $sectionPageUrl;
        $this->canonicalPageUrl = $canonicalPageUrl;
        $this->picture = $picture;
        $this->description = $description;
        $this->properties = $properties;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['API_CODE'],
            $fields['VERSION'],
            $fields['TIMESTAMP_X'],
            $fields['IBLOCK_TYPE_ID'],
            $fields['LID'],
            $fields['CODE'],
            $fields['NAME'],
            $fields['ACTIVE'],
            $fields['SORT'],
            $fields['LIST_PAGE_URL'],
            $fields['DETAIL_PAGE_URL'],
            $fields['SECTION_PAGE_URL'],
            $fields['CANONICAL_PAGE_URL'],
            PictureDto::byId($fields['PICTURE']),
            $fields['DESCRIPTION'],
            $fields['PROPERTIES']
        );
    }
}