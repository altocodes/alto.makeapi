<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;

class BadRequestException extends BaseHttpException
{
    protected string $errorCode = 'bad_request';
    protected HttpStatus $httpStatus = HttpStatus::BAD_REQUEST;
}