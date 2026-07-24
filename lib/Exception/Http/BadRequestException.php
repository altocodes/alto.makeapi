<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BadRequestErrorResponse',
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
                ref: "#/components/schemas/BadRequestError"
            )
        )
    ]
)]
#[OA\Schema(
    schema: "BadRequestError",
    properties: [
        new OA\Property(
            property: "message",
            description: "Информация об ошибке",
            type: "string",
            example: "Текст ошибки"
        ),
        new OA\Property(
            property: "code",
            description: "Код ошибки",
            type: "string",
            example: "bad_request"
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
class BadRequestException extends BaseHttpException
{
    protected string $errorCode = 'bad_request';
    protected HttpStatus $httpStatus = HttpStatus::BAD_REQUEST;
}