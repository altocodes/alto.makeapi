<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Enum\HttpStatus;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Error;
use Bitrix\Main\Request;

class BaseController extends Controller
{
    protected $response;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->response = Application::getInstance()->getContext()->getResponse();
    }

    protected function processBeforeAction(Action $action)
    {
        if (count($this->getErrors()) > 0) {
            return false;
        }

        return parent::processBeforeAction($action);
    }

    protected function response($data)
    {
        $response = new Json($data);

        return $response->send();
    }

    /**
     * Обработка ошибок
     *
     * @param string $error
     * @param int $ststus
     * @return void
     */
    protected function setError(string $error, int $status = HttpStatus::ERROR->value)
    {
        $this->addError(new Error(
            $error,
            'request_error',
        ));
        $this->response->setStatus($status);
    }
}