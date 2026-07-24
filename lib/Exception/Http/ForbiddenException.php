<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;

class ForbiddenException extends BaseHttpException
{
    protected string $errorCode = 'forbidden';
    protected HttpStatus $httpStatus = HttpStatus::FORBIDDEN;
}