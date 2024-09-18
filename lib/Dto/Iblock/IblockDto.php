<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;

class IblockDto extends BaseDto
{
    public readonly int $id;
    public readonly string $api_code;
    public readonly string $version;
    public readonly string $timestamp;
    public readonly string $iblock_type_id;
    public readonly string $site_id;
    public readonly string $code;
    public readonly string $name;
    public readonly bool $active;
    public readonly int $sort;
    public readonly string $list_page_url;
    public readonly string $detail_page_url;
    public readonly string $section_page_url;
    public readonly string $canonical_page_url;
    public readonly string $description;
    public readonly ?array $properties;

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
        string $description,
        ?array $properties
    ) {
        $this->id = $id;
        $this->api_code = $apiCode;
        $this->version = $version;
        $this->timestamp = $timestamp;
        $this->iblock_type_id = $iblockTypeId;
        $this->site_id = $siteId;
        $this->code = $code;
        $this->name = $name;
        $this->active = $active;
        $this->sort = $sort;
        $this->list_page_url = $listPageUrl;
        $this->detail_page_url = $detailPageUrl;
        $this->section_page_url = $sectionPageUrl;
        $this->canonical_page_url = $canonicalPageUrl;
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
            $fields['DESCRIPTION'],
            $fields['PROPERTIES']
        );
    }
}