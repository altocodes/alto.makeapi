<?php

namespace Alto\MakeApi\Dto;

class ListDto extends BaseDto
{
    private int $page;
    private int $limit;
    private int $total;
    private array $items;

    public function __construct(
        int $page,
        int $limit,
        int $total,
        array $items
    ) {
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
        $this->items = $items;
    }
}