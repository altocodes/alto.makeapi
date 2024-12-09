<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\MenuService;
use Bitrix\Main\Engine\Action;

class MenuController extends BaseController
{
    private MenuService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new MenuService();

        return parent::processBeforeAction($action);
    }

    public function getAction(): array
    {
        $type = $this->request->get('type');

        // TODO: валидация

        return $this->service->getByCode($type);
    }
}