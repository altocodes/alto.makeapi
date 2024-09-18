<?php

namespace Alto\MakeApi\Dto\Iblock\Property\Value;

use Alto\MakeApi\Dto\BaseDto;

class DirectoryValueDto extends BaseDto
{
    public readonly int $id;
    public readonly string $name;
    public readonly string $xml_id;
    public readonly string $description;
    public readonly string $full_description;
    public readonly mixed $file;

    /**
     * @param int $id
     * @param string $name
     * @param string $xml_id
     */
    public function __construct(
        int    $id,
        string $name,
        string $xml_id,
        string $description,
        string $full_description,
        mixed $file,
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->xml_id = $xml_id;
        $this->description = $description;
        $this->full_description = $full_description;
        $this->file = $file;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['UF_NAME'],
            $fields['UF_XML_ID'],
            $fields['UF_DESCRIPTION'],
            $fields['UF_FULL_DESCRIPTION'],
            $fields['UF_FILE'],
        );
    }
}