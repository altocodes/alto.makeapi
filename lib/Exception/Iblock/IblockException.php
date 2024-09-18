<?php

namespace Alto\MakeApi\Exception\Iblock;

use Alto\MakeApi\Enum\HttpStatus;
use Alto\MakeApi\Exception\BaseHttpResponseException;
use Alto\MakeApi\Exception\Http\BaseHttpException;

class IblockException extends BaseHttpException
{
    protected HttpStatus $httpStatus = HttpStatus::BAD_REQUEST;
}