<?php

namespace Alto\MakeApi\Controller;

use Exception;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\ListDto;
use Alto\MakeApi\Service\IblockService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Request;

// TODO: сейчас код ответа всегда 200, подумать как лучше управлять кодом ответа
class IblockController extends BaseController
{
    private $service;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        try {
            $this->service = new IblockService($this->request->get('iblock_code'));
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    public function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod(
                [ActionFilter\HttpMethod::METHOD_GET]
            ),
            new ActionFilter\Csrf(false),
        ];
    }

    /**
     * Получение информации об инфоблоке
     * @return IblockDto
     */
    public function infoAction(): IblockDto
    {
        $response = [];

        try {
            $response = $this->service->getInfo();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }

        return $response;
    }

    /**
     * Получение списка элементов
     * @return ListDto
     */
    public function listAction(): ListDto
    {
        $filter = $this->request->get('filter') ?? [];
        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? 10;
        $response = [];

        try {
            $response = $this->service->getList($filter, $page, $limit);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }

        return $response;
    }

    /**
     * Получение информации об элементе
     * @return array
     */
    public function elementAction()
    {
        $response = [];

        try {
            $response = $this->service->getElementById($this->request->get('element_id'));
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }

        return $response;
    }
}