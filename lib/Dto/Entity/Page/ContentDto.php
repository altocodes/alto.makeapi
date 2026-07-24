<?php

namespace Alto\MakeApi\Dto\Entity\Page;

use Alto\MakeApi\Dto\BaseDto;
use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: "ContentData",
    properties: [
        new OA\Property(property: "page", type: "string", example: ""),
        new OA\Property(property: "code", type: "string", example: "footer.phones"),
        new OA\Property(property: "type", type: "string", example: "text"),
        new OA\Property(property: "content", type: "string", example: "<a href=\"tel:79161602713\">+7 916 160 27 13</a>\r\n<a href=\"tel:79161602713\">+7 495 504 1552</a>"),
        new OA\Property(property: "sort", type: "integer", example: 0)
    ]
)]
class ContentDto extends BaseDto
{
    public readonly ?string $page;
    public readonly ?string $code;
    public readonly ?string $type;
    public readonly string|array|null $content;
    public readonly ?int $sort;

    #[OA\Schema(
        schema: "ContentResponse",
        properties: [
            new OA\Property(property: "status", type: "string", example: "success"),
            new OA\Property(
                property: "data",
                ref: "#/components/schemas/ContentData",
                type: "object"
            ),
            new OA\Property(
                property: "errors", 
                type: "array", 
                items: new OA\Items(type: "string")
            )
        ]
    )]
    #[OA\Schema(
        schema: "ContentArrayResponse",
        properties: [
            new OA\Property(
                property: "status",
                type: "string",
                example: "success"
            ),
            new OA\Property(
                property: "data",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/ContentData")
            ),
            new OA\Property(
                property: "errors",
                type: "array",
                items: new OA\Items(type: "string")
            )
        ]
    )]
    public function __construct(
        ?string $page,
        ?string $code,
        ?string $type,
        string|array|null $content = null,
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
        return new self(
            $fields['UF_PAGE'],
            $fields['UF_CODE'],
            $fields['TYPE_XML_ID'],
            $fields['UF_CONTENT'],
            $fields['UF_SORT'] ?: 0,
        );
    }
}