<?php

namespace Alto\MakeApi\Enum;

enum HttpStatus: int {
    case SUCCESS = 200;
    case REDIRECT_PERM = 301;
    case REDIRECT_FOUND = 302;
    case NOT_FOUND = 404;
    case ERROR = 500;
}