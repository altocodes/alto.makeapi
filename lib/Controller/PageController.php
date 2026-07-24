<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\Iblock\IblockService;
use Bitrix\Main\Engine\Action;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "page",
    description: "Методы для работы с контентом"
)]
class PageController extends BaseController
{
    private IblockService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new IblockService('pages');

        return parent::processBeforeAction($action);
    }

    #[OA\Get(
        path: "/api/v1/page/{code}",
        description: "Получение контента по коду",
        summary: "Получить контент по коду",
        tags: ["content"],
        parameters: [
            new OA\Parameter(
                name: "code",
                description: "Символьный код страницы",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "services"
            )
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Успешный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/ElementResponse"
        )
    )]
    #[OA\Response(
        response: "404",
        description: "Элемент не найден",
        content: new OA\JsonContent(
            ref: "#/components/schemas/NotFoundErrorResponse"
        )
    )]
    public function getByCodeAction(string $code)
    {
        return $this->service->getElementByCode($code);
    }
}