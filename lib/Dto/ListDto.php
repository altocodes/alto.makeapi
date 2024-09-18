<?php

namespace Alto\MakeApi\Dto;

class ListDto extends BaseDto
{
    public readonly PaginationDto $pagination;
    public readonly array $items;

    public function __construct(
        PaginationDto $pagination,
        array $items
    ) {
        $this->pagination = $pagination;
        $this->items = $items;
    }
}