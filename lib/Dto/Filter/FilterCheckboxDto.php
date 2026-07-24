<?php

namespace Alto\MakeApi\Dto\Filter;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Enum\FieldsType;
use OpenApi\Attributes as OA;

class FilterCheckboxDto extends BaseDto
{
    public readonly string $name;
    public readonly string $code;
    public readonly array $values;
    public readonly ?string $type;

    #[OA\Schema(
        schema: "FilterCheckbox",
        description: "Фильтр типа checkbox",
        properties: [
            new OA\Property(
                property: "name",
                description: "Название фильтра",
                type: "string",
                example: "Цвет"
            ),
            new OA\Property(
                property: "code",
                description: "Код фильтра",
                type: "string",
                example: "color"
            ),
            new OA\Property(
                property: "values",
                description: "Значения фильтра",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/FilterValue")
            ),
            new OA\Property(
                property: "type",
                description: "Тип фильтра",
                type: "string",
                example: "checkbox"
            )
        ]
    )]
    public function __construct(string $name, string $code, array $values, string $type = null)
    {
        $this->name = $name;
        $this->code = $code;
        $this->values = $values;
        $this->type = FieldsType::tryFrom($type)->name;
    }
}