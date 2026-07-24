<?php

namespace Alto\MakeApi\Dto\Filter;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Enum\FieldsType;
use OpenApi\Attributes as OA;

class FilterRangeDto extends BaseDto
{
    public readonly string $name;
    public readonly string $code;
    public readonly FilterRangeValueDto $range;
    public readonly string $type;

    #[OA\Schema(
        schema: "FilterRange",
        description: "Фильтр типа range",
        properties: [
            new OA\Property(
                property: "name",
                description: "Название фильтра",
                type: "string",
                example: "Цена"
            ),
            new OA\Property(
                property: "code",
                description: "Код фильтра",
                type: "string",
                example: "price"
            ),
            new OA\Property(
                property: "range",
                ref: "#/components/schemas/FilterRangeValue",
                description: "Диапазон значений"
            ),
            new OA\Property(
                property: "type",
                description: "Тип фильтра",
                type: "string",
                example: "range"
            )
        ]
    )]
    public function __construct(string $name, string $code, FilterRangeValueDto $range)
    {
        $this->name = $name;
        $this->code = $code;
        $this->range = $range;
        $this->type = FieldsType::RANGE->name;
    }
}