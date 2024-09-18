<?php

namespace Alto\MakeApi\Dto;

class PaginationDto extends BaseDto
{
    public readonly int $page;
    public readonly int $total_page;
    public readonly int $limit;
    public readonly int $count_items;

    public function __construct(int $page, int $total_page, int $limit, int $count_items)
    {
        $this->page = $page;
        $this->total_page = $total_page;
        $this->limit = $limit;
        $this->count_items = $count_items;
    }
}