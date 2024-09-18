<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\ContentService;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;

class ContentController extends BaseController
{
    private ContentService $service;

    protected function processBeforeAction(Action $action)
    {
        $siteId = $this->request->get('site');

        if (!$siteId) {
            $siteId = Context::getCurrent()->getSite();
        }

        $this->service = new ContentService($siteId);

        return parent::processBeforeAction($action);
    }

    public function getByCodeAction(string $code)
    {
        return $this->service->getByCode($code);
    }

    public function getByPageAction()
    {
        $page = $this->request->get('page');

        // TODO: валидация

        return $this->service->getByPage($page);
    }
}