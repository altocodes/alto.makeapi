<?php

namespace Alto\MakeApi\Dto\UserType;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\Property\TextDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BlockContentDto',
    description: 'Блок контента (заголовок + структурированный текст)',
    required: ['title', 'content', 'sort'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Вводный блок'),
        new OA\Property(property: 'content', ref: '#/components/schemas/TextDto'),
        new OA\Property(property: 'sort', description: 'Порядок блока (как в админке)', type: 'integer', example: 10),
    ],
    type: 'object'
)]
class BlockContentDto extends BaseDto
{
    public readonly string $title;
    public readonly TextDto $content;
    public readonly int $sort;

    public function __construct(string $title, TextDto $content, int $sort)
    {
        $this->title = $title;
        $this->content = $content;
        $this->sort = $sort;
    }
}
