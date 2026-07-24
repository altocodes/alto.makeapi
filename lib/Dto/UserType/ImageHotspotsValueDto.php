<?php

namespace Alto\MakeApi\Dto\UserType;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ImageHotspotsValueDto',
    description: 'Точка на схеме: фон из настроек свойства и координаты одной метки (в процентах 0–100)',
    required: ['background'],
    properties: [
        new OA\Property(
            property: 'background',
            description: 'Файл фона из настроек свойства',
            ref: '#/components/schemas/PictureDto',
            nullable: true
        ),
        new OA\Property(
            property: 'x_percent',
            description: 'Позиция X, % от ширины (null — точка не задана)',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'y_percent',
            description: 'Позиция Y, % от высоты (null — точка не задана)',
            type: 'number',
            format: 'float',
            nullable: true
        ),
    ],
    type: 'object'
)]
class ImageHotspotsValueDto extends BaseDto
{
    public function __construct(
        public readonly ?PictureDto $background,
        public readonly ?float $x_percent,
        public readonly ?float $y_percent,
    ) {
    }
}
