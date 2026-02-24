<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\Meta\MetaService;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: "/api/v1/meta",
        summary: "Получение meta-данных для страницы",
        tags: ["general"],
        parameters: [
            new OA\Parameter(
                name: "url",
                description: "URL страницы",
                in: "query",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                ),
                example: "/contacts",
            )
        ]

    )]
    #[OA\Response(
        response: "200",
        description: "Список meta-тегов",
        content: new OA\JsonContent(
            ref: "#/components/schemas/MetaResponse"
        )
    )]
    #[OA\Response(
        response: "404",
        description: "Данные не получены",
        content: new OA\JsonContent(
            ref: "#/components/schemas/NotFoundErrorResponse"
        )
    )]
    #[OA\Response(
        response: "500",
        description: "Ошибка получения данных",
        content: new OA\JsonContent(
            ref: "#/components/schemas/BadRequestErrorResponse"
        )
    )]
    public function getForPageAction()
    {
        $url = $this->request->get('url');

        // TODO: валидация

        return $this->service->getForPage($url);
    }
}