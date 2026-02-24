<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Enum\HttpStatus;

class NotFoundController extends BaseController
{
    public function notFoundAction(): array
    {
        $this->setHttpStatus(HttpStatus::NOT_FOUND);
        
        return [
            'status' => 'error',
            'data' => null,
            'errors' => ['Not Found']
        ];
    }
}