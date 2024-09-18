<?php

namespace Alto\MakeApi\Dto\Page;

use Alto\MakeApi\Dto\BaseDto;

class ContentDto extends BaseDto
{
    public readonly ?string $page;
    public readonly ?string $code;
    public readonly ?string $type;
    public readonly ?string $content;
    public readonly ?int $sort;

    public function __construct(
        ?string $page,
        ?string $code,
        ?string $type,
        ?string $content,
        int    $sort,
    )
    {
        $this->page = $page;
        $this->code = $code;
        $this->type = $type;
        $this->content = $content;
        $this->sort = $sort;
    }

    public static function fromArray(array $fields): self
    {
        var_dump($fields);
        return new self(
            $fields['UF_PAGE'],
            $fields['UF_CODE'],
            $fields['TYPE_XML_ID'],
            $fields['UF_CONTENT'],
            $fields['UF_SORT'] ?: 0,
        );
    }
}