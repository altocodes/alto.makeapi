<?php

namespace Alto\MakeApi\Dto;

use Alto\MakeApi\Dto\BaseDto;

class MetaDto extends BaseDto
{
    public readonly string $title;
    public readonly string $description;
    public readonly string $robots;
    public readonly string $canonical;

    public function __construct(string $title, string $description, string $robots, string $canonical)
    {
        $this->title = $title;
        $this->description = $description;
        $this->robots = $robots;
        $this->canonical = $canonical;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['TITLE'] ?? '',
            $fields['DESCRIPTION'] ?? '',
            $fields['ROBOTS'] ?? '',
            $fields['CANONICAL'] ?? '',
        );
    }
}