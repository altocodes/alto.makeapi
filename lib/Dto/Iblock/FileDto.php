<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;

class FileDto extends BaseDto
{
    public readonly int $id;
    public readonly  string $url;
    public readonly  string $name;
    public readonly  string $alt;
    public readonly  string $type;

    public function __construct(
        int $id,
        string $url,
        string $name,
        string $alt,
    )
    {
        $this->id = $id;
        $this->url = $url;
        $this->name = $name;
        $this->alt = $alt;
        $this->type = 'file';
    }
}