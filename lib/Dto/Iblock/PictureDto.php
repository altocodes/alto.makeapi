<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;

class PictureDto extends BaseDto
{
    public readonly  int $id;
    public readonly  array $url;
    public readonly  string $alt;
    public readonly string $type;

    public function __construct(
        int $id,
        array $url,
        string $alt,
    )
    {
        $this->id = $id;
        $this->url = $url;
        $this->alt = $alt;
        $this->type = 'image';
    }
}