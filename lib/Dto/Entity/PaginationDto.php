<?php

namespace Alto\MakeApi\Dto\Entity;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PaginationDto",
    properties: [
        new OA\Property(
            property: "page",
            description: "Номер страницы",
            type: "int",
            example: 1
        ),
        new OA\Property(
            property: "total_page",
            description: "Всего страниц",
            type: "int",
            example: 1
        ),
        new OA\Property(
            property: "limit",
            description: "Кол-во записей на страницу",
            type: "int",
            example: 1
        ),
        new OA\Property(
            property: "count_items",
            description: "Всего записей",
            type: "int",
            example: 1
        )
    ]
)]
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