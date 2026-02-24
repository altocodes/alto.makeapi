<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Response\SearchResultDto;
use Alto\MakeApi\Service\SearchService;
use Bitrix\Main\Engine\Action;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "search", description: "Методы поиска")]
class SearchController extends BaseController
{
    private SearchService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new SearchService();
        return parent::processBeforeAction($action);
    }

    #[OA\Get(
        path: "/api/v1/search",
        description: "Поиск товаров, категорий и статей по текстовому запросу",
        summary: "Универсальный поиск по сайту",
        tags: ["search"],
        parameters: [
            new OA\Parameter(
                name: "query",
                description: "Поисковый запрос",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "тест"
            ),
            new OA\Parameter(
                name: "type",
                description: "Типы для поиска (через запятую: products,articles), если iblock - каталог, поиск будет так же по разделам",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "products,articles"
            ),
            new OA\Parameter(
                name: "page",
                description: "Страница пагинации (только для товаров)",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 1,
                    minimum: 1
                ),
                example: 1
            ),
            new OA\Parameter(
                name: "limit",
                description: "Количество элементов на странице (только для товаров)",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 20,
                    minimum: 1,
                    maximum: 100
                ),
                example: 20
            ),
            new OA\Parameter(
                name: "sort",
                description: "Поле для сортировки товаров",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["ID", "NAME", "SORT", "DATE_CREATE"],
                    default: "NAME"
                ),
                example: "NAME"
            ),
            new OA\Parameter(
                name: "order",
                description: "Порядок сортировки",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    default: "ASC",
                    enum: ["ASC", "DESC"]
                ),
                example: "ASC"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Успешный поиск",
                content: new OA\JsonContent(ref: "#/components/schemas/SearchResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Некорректный запрос",
                content: new OA\JsonContent(ref: "#/components/schemas/BadRequestErrorResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Внутренняя ошибка сервера",
                content: new OA\JsonContent(ref: "#/components/schemas/ServerErrorResponse")
            )
        ]
    )]
    public function searchAction(): SearchResultDto
    {
        $query = $this->request->get('query');
        $type = $this->request->get('type');
        $page = (int)$this->request->get('page') ?: 1;
        $limit = (int)$this->request->get('limit') ?: 20;
        $sort = $this->request->get('sort');
        $order = strtoupper($this->request->get('order')) ?: 'ASC';

        if (!$query) {
            throw new \Exception('Параметр query обязателен');
        }

        if (!$type) {
            $type = ['products', 'articles'];
        } else {
            $type = explode(',', $type);
        }

        return $this->service->getResult($query, $type, $page, $limit, $sort, $order);
    }
}
