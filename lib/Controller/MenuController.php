<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\MenuService;
use Bitrix\Main\Engine\Action;
use OpenApi\Attributes as OA;

class MenuController extends BaseController
{
    private MenuService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new MenuService();

        return parent::processBeforeAction($action);
    }

    #[OA\Get(
        path: "/api/v1/menu",
        summary: "Получение меню",
        tags: ["general"],
        parameters: [
            new OA\Parameter(
                name: "code",
                description: "Код меню",
                in: "query",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                ),
                example: "top",
            )
        ]

    )]
    #[OA\Response(
        response: "200",
        description: "Список пунктов меню",
        content: new OA\JsonContent(
            ref: "#/components/schemas/MenuResponse"
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
    public function getAction(): array
    {
        $code = $this->request->get('code');

        // TODO: валидация

        return $this->service->getByCode($code);
    }
}