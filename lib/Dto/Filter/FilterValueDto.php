<?php

namespace Alto\MakeApi\Dto\Filter;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use OpenApi\Attributes as OA;

class FilterValueDto extends BaseDto
{
    public readonly string $name;
    public readonly string $value;
    public readonly ?PictureDto $file;
    public readonly bool $checked;
    public readonly bool $disabled;

    #[OA\Schema(
        schema: "FilterValue",
        description: "Значение фильтра типа checkbox",
        properties: [
            new OA\Property(
                property: "name",
                description: "Название значения",
                type: "string",
                example: "Красный"
            ),
            new OA\Property(
                property: "value",
                description: "Значение",
                type: "string",
                example: "red"
            ),
            new OA\Property(
                property: "file",
                ref: "#/components/schemas/PictureDto",
                description: "Файл (например, иконка)",
                nullable: true
            ),
            new OA\Property(
                property: "checked",
                description: "Выбрано ли значение",
                type: "boolean",
                example: true
            ),
            new OA\Property(
                property: "disabled",
                description: "Отключено ли значение",
                type: "boolean",
                example: false
            )
        ]
    )]
    public function __construct(string $name, string $value, ?PictureDto $file, bool $checked, bool $disabled)
    {
        $this->name = $name;
        $this->value = $value;
        $this->file = $file;
        $this->checked = $checked;
        $this->disabled = $disabled;
    }
}