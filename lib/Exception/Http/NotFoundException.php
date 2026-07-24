<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NotFoundErrorResponse',
    properties: [
        new OA\Property(
            property: "status",
            type: "string",
            example: "error"
        ),
        new OA\Property(
            property: "data",
            type: "null",
            example: null
        ),
        new OA\Property(
            property: "errors",
            description: "Массив ошибок",
            type: "array",
            items: new OA\Items(
                ref: "#/components/schemas/NotFoundError"
            )
        )
    ]
)]
#[OA\Schema(
    schema: "NotFoundError",
    properties: [
        new OA\Property(
            property: "message",
            description: "Информация об ошибке",
            type: "string"
        ),
        new OA\Property(
            property: "code",
            description: "Код ошибки",
            type: "string",
            example: "not_found"
        ),
        new OA\Property(
            property: "customData",
            description: "Подробности ошибки",
            type: "object",
            properties: [
                new OA\Property(
                    property: "key",
                    type: "string",
                    example: "value"
                )
            ]
        )
    ]
)]
class NotFoundException extends BaseHttpException
{
    protected string $errorCode = 'not_found';
    protected HttpStatus $httpStatus = HttpStatus::NOT_FOUND;
}