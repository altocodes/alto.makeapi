<?php

namespace Alto\MakeApi\Dto\Response;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\PaginationDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SearchResult",
    description: "Результат поиска"
)]
class SearchResultDto extends BaseDto
{
    #[OA\Property(
        description: "Результаты поиска по разным типам",
        type: "object",
        additionalProperties: new OA\AdditionalProperties(
            type: "array",
            items: new OA\Items(type: "object")
        )
    )]
    public readonly array $items;

    #[OA\Property(
        ref: "#/components/schemas/PaginationDto",
        description: "Пагинация (только при поиске товаров)",
        nullable: true
    )]
    public readonly ?PaginationDto $pagination;

    public function __construct(array $items, ?PaginationDto $pagination = null)
    {
        $this->items = $items;
        $this->pagination = $pagination;
    }
}
