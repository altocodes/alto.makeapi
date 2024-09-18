<?php

namespace Alto\MakeApi\ActionFilter;

use Alto\MakeApi\Controller\BaseController;
use Alto\MakeApi\Enum\HttpStatus;
use Bitrix\Main\Engine\ActionFilter\Base;

class BaseActionFilter extends Base
{
    protected function setHttpStatus(HttpStatus $httpStatus)
    {
        $controller = $this->getAction()->getController();
        if ($controller instanceof BaseController) {
            $controller->setHttpStatus($httpStatus);
        }
    }
}