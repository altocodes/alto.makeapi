<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Iblock\Element\ElementDto;
use Alto\MakeApi\Dto\Iblock\ElementDetailDto;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\Iblock\IblockService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\ListDto;

class IblockController extends BaseController
{
    private IblockService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new IblockService($this->request->get('iblock_code'));

        return parent::processBeforeAction($action);
    }

    /**
     * Получение информации об инфоблоке
     *
     * @return IblockDto
     */
    public function infoAction(): IblockDto
    {
        return $this->service->getInfo();
    }

    /**
     * Получение списка элементов
     *
     * @return ListDto
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function listAction(): ListDto
    {
        // TODO: валидация
        $filter = $this->request->get('filter') ?? [];
        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? 10;
        $sort = $this->request->get('sort') ?? IblockRepository::SORT_BY_DEFAULT;
        $order = $this->request->get('order') ?? IblockRepository::SORT_ORDER_DEFAULT;

        return $this->service->getList($filter, $page, $limit, $sort, $order);
    }

    /**
     * Получение информации об элементе
     * @return \Alto\MakeApi\Dto\Iblock\ElementDetailDto|void
     * @throws \Alto\MakeApi\Exception\Http\BaseHttpException
     */
    public function elementAction(): ElementDetailDto
    {
        // TODO: валидация
        if ($element_code = $this->request->get('element_code')) {
            return $this->service->getElementByCode($element_code);
        } elseif ($element_id = $this->request->get('element_id')) {
            return $this->service->getElementById($element_id);
        }
    }
}