<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;

class InternalServerException extends BaseHttpException
{
    protected string $errorCode = 'internal_server_error';
    protected HttpStatus $httpStatus = HttpStatus::ERROR;
}