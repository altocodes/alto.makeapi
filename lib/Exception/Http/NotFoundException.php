<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;

class NotFoundException extends BaseHttpException
{
    protected string $errorCode = 'not_found';
    protected HttpStatus $httpStatus = HttpStatus::NOT_FOUND;
}