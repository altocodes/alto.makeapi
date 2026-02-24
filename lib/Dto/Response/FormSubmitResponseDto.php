<?php

namespace Alto\MakeApi\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "FormSubmitResponseDto",
    properties: [
        new OA\Property(
            property: "status",
            type: "string",
            example: "success"
        ),
        new OA\Property(
            property: "message",
            type: "string",
            example: "Данные формы сохранены"
        ),
        new OA\Property(
            property: "form_code",
            description: "Код формы",
            type: "string",
            example: "contact_us"
        ),
    ]
)]
class FormSubmitResponseDto
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $form_code = null
    ) {}

    
}