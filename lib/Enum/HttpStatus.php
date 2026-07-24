<?php

namespace Alto\MakeApi\Enum;

enum HttpStatus: int {
    case SUCCESS = 200;
    case REDIRECT_PERM = 301;
    case REDIRECT_FOUND = 302;
    case BAD_REQUEST = 400;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case IM_A_TEAPOT = 418;
    case ERROR = 500;
}