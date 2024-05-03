<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;
use Bitrix\Main\FileTable;

class PictureDto extends BaseDto
{
    private int $id;
    private string $name;
    private string $url;
    private string $description;
    private string $width;
    private string $height;

    public function __construct(
        int $id,
        string $name,
        string $url,
        string $description,
        string $width,
        string $height
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->description = $description;
        $this->width = $width;
        $this->height = $height;
    }

    public static function byId(?int $id): ?self
    {
        if (!$id) {
            return null;
        }

        $file = FileTable::getById($id)->fetch();
        if (!$file) {
            return null;
        }

        return new self(
            $id,
            $file['ORIGINAL_NAME'],
            \CFile::GetFileSRC($file),
            $file['DESCRIPTION'],
            $file['WIDTH'],
            $file['HEIGHT'],
        );
    }
}