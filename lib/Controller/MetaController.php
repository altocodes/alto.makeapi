<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\Meta\MetaService;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;

class MetaController extends BaseController
{
    private MetaService $service;

    protected function processBeforeAction(Action $action)
    {
        $siteId = $this->request->get('site');

        if (!$siteId) {
            $siteId = Context::getCurrent()->getSite();
        }

        $this->service = MetaService::getInstance();
        $this->service->setSiteId($siteId);

        return parent::processBeforeAction($action);
    }

    public function getForPageAction()
    {
        $page = $this->request->get('page');

        // TODO: валидация

        return $this->service->getForPage($page);
    }
}