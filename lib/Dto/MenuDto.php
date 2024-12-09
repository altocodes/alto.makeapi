<?php

namespace Alto\MakeApi\Dto;

class MenuDto extends BaseDto
{
    public readonly string $title;
    public readonly string $url;
    public readonly array $params;

    public function __construct(string $title, string $url, array $params)
    {
        $this->title = $title;
        $this->url = $url;
        $this->params = $params;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields[0] ?? '',
            $fields[1] ?? '',
            $fields[3] ?? []
        );
    }
}