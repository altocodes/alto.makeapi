<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Iblock\Section\SectionDto;
use Alto\MakeApi\Dto\Iblock\SectionDetailDto;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\Iblock\IblockSectionService;
use Bitrix\Main\Engine\Action;
use Alto\MakeApi\Dto\ListDto;

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

    public function sectionAction(): SectionDetailDto
    {
        if ($section_code = $this->request->get('section_code')) {
            return $this->service->getSectionByCode($section_code);
        } elseif ($section_id = $this->request->get('section_id')) {
            return $this->service->getSectionById($section_id);
        }
    }
}