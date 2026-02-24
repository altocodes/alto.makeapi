<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Service\ContentService;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "content",
    description: "Методы для работы с контентом"
)]
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

    #[OA\Get(
        path: "/api/v1/content/{code}",
        description: "Получение контента по коду",
        summary: "Получить контент по коду",
        tags: ["content"],
        parameters: [
            new OA\Parameter(
                name: "code",
                description: "Код контента",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "footer.phones"
            ),
            new OA\Parameter(
                name: "site",
                description: "ID сайта (опционально)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "s1"
            )
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Успешный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/ContentResponse"
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
        return $this->service->getByCode($code);
    }

    #[OA\Get(
        path: "/api/v1/content/pages/{page}",
        description: "Получение списка контента по странице",
        summary: "Получить все элементы контента для указанной страницы",
        tags: ["content"],
        parameters: [
            new OA\Parameter(
                name: "page",
                description: "Название страницы для поиска контента",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "main"
            ),
            new OA\Parameter(
                name: "site",
                description: "ID сайта (опционально)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "s1"
            )
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Успешный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/ContentArrayResponse"
        )
    )]
    #[OA\Response(
        response: "404",
        description: "Контент не найден",
        content: new OA\JsonContent(
            ref: "#/components/schemas/NotFoundErrorResponse"
        )
    )]
    public function getByPageAction()
    {
        $page = $this->request->get('page');

        // TODO: валидация

        return $this->service->getByPage($page);
    }
}