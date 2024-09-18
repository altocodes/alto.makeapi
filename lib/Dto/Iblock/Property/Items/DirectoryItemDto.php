<?php

namespace Alto\MakeApi\Dto\Iblock\Property\Items;

use Alto\MakeApi\Dto\BaseDto;

class DirectoryItemDto extends BaseDto
{
    public readonly int $id;
    public readonly string $name;
    public readonly int $sort;
    public readonly string $xml_id;
    public readonly string $link;
    public readonly string $description;
    public readonly string $full_description;
    public readonly bool $default;
    public readonly mixed $file;

    public function __construct(
        int    $id,
        string $name,
        int $sort,
        string $xml_id,
        string $link,
        string $description,
        string $full_description,
        bool $default,
        mixed $file,
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->sort = $sort;
        $this->xml_id = $xml_id;
        $this->link = $link;
        $this->description = $description;
        $this->full_description = $full_description;
        $this->default = $default;
        $this->file = $file;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['UF_NAME'],
            $fields['UF_SORT'],
            $fields['UF_XML_ID'],
            $fields['UF_LINK'],
            $fields['UF_DESCRIPTION'],
            $fields['UF_FULL_DESCRIPTION'],
            $fields['UF_DEF'] == 1,
            $fields['UF_FILE'] ?? '',
        );
    }
}