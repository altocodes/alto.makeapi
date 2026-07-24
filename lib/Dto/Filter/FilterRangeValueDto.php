<?php

namespace Alto\MakeApi\Dto\Filter;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;

class FilterRangeValueDto extends BaseDto
{
    public readonly int $min;
    public readonly int $max;
    public readonly int $current_min;
    public readonly int $current_max;

    #[OA\Schema(
        schema: "FilterRangeValue",
        description: "Диапазон значений для фильтра",
        properties: [
            new OA\Property(
                property: "min",
                description: "Минимальное значение",
                type: "integer",
                example: 0
            ),
            new OA\Property(
                property: "max",
                description: "Максимальное значение",
                type: "integer",
                example: 10000
            ),
            new OA\Property(
                property: "current_min",
                description: "Текущее минимальное значение",
                type: "integer",
                example: 1000
            ),
            new OA\Property(
                property: "current_max",
                description: "Текущее максимальное значение",
                type: "integer",
                example: 5000
            )
        ]
    )]
    public function __construct(int $min, int $max, int $currentMin, int $currentMax)
    {
        $this->min = $min;
        $this->max = $max;
        $this->current_min = $currentMin;
        $this->current_max = $currentMax;
    }
}