<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Iblock\Section\SectionDto;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\IblockSectionService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\ListDto;
use Alto\MakeApi\Service\IblockService;

class IblockSectionController extends BaseController
{
    private IblockSectionService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new IblockSectionService($this->request->get('iblock_code'));

        return parent::processBeforeAction($action);
    }

    public function listAction(): ListDto
    {
        // TODO: валидация

        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? 10;
        $sort = $this->request->get('sort') ?? IblockRepository::SORT_BY_DEFAULT;
        $order = $this->request->get('order') ?? IblockRepository::SORT_ORDER_DEFAULT;

        return $this->service->getSections($page, $limit, $sort, $order);
    }

    public function sectionAction(): SectionDto
    {
        if ($section_code = $this->request->get('section_code')) {
            return $this->service->getSectionByCode($section_code);
        } elseif ($section_id = $this->request->get('section_id')) {
            return $this->service->getSectionById($section_id);
        }
    }
}