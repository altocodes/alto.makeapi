<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Entity\Iblock\ElementDetailDto;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\Iblock\IblockService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Alto\MakeApi\Dto\Entity\Iblock\IblockDto;
use Alto\MakeApi\Dto\Entity\ListDto;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "iblock",
    description: "Методы для работы с инфоблоками"
)]
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

    #[OA\Get(
        path: "/api/v1/iblock/{iblock_code}/elements",
        description: "Возвращает пагинированный список элементов инфоблока Bitrix с фильтрацией и сортировкой",
        summary: "Получить список элементов инфоблока",
        tags: ["iblock"],
        parameters: [
            new OA\Parameter(
                name: "iblock_code",
                description: "Код инфоблока (символьный код API)",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "catalog"
            ),
            new OA\Parameter(
                name: "limit",
                description: "Количество элементов на странице",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 20,
                    maximum: 100,
                    minimum: 1
                ),
                example: 20
            ),
            new OA\Parameter(
                name: "page",
                description: "Номер страницы (начиная с 1)",
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
                name: "sort",
                description: "Поле для сортировки (ID, NAME, DATE_CREATE, SORT и т.д.)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "NAME"
            ),
            new OA\Parameter(
                name: "order",
                description: "Направление сортировки (ASC, DESC)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "ASC"
            ),
            new OA\Parameter(
                name: "filter",
                description: "Фильтр элементов инфоблока. Передаётся как query-параметр в формате filter[ПОЛЕ]=ЗНАЧЕНИЕ. "
                    . "Поддерживаемые поля: ID, NAME, CODE, DATE_CREATE, ACTIVE, а также свойства инфоблока (PROPERTY_XXX). "
                    . "Пример URL: ?filter[ACTIVE]=Y&filter[PROPERTY_COLOR]=red&filter[NAME]=Товар",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "object",
                    example: ["ACTIVE" => "Y", "PROPERTY_COLOR" => "red"],
                    additionalProperties: new OA\AdditionalProperties(type: "string")
                ),
                style: "deepObject",
                explode: true
            ),
            new OA\Parameter(
                name: "properties",
                description: "Список символьных кодов свойств инфоблока через запятую. Пример: properties=COLOR,SIZE,MATERIAL",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "COLOR,SIZE,MATERIAL"
                )
            )
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Успешный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/IblockElementsResponse"
        )
    )]
    #[OA\Response(
        response: "400",
        description: "Некорректный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/BadRequestErrorResponse"
        )
    )]
    #[OA\Response(
        response: "404",
        description: "Инфоблок не найден",
        content: new OA\JsonContent(
            ref: "#/components/schemas/NotFoundErrorResponse"
        )
    )]
    #[OA\Response(
        response: "500",
        description: "Внутренняя ошибка сервера",
        content: new OA\JsonContent(
            ref: "#/components/schemas/BadRequestErrorResponse"
        )
    )]
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
        $propertiesRaw = $this->request->get('properties');
        $properties = $propertiesRaw ? array_filter(array_map('trim', explode(',', $propertiesRaw))) : [];

        return $this->service->getList($filter, $page, $limit, $sort, $order, $properties);
    }

    #[OA\Get(
        path: "/api/v1/iblock/{iblock_code}/element",
        description: "Возвращает данные элемента инфоблока Bitrix",
        summary: "Получить элемент инфоблока",
        tags: ["iblock"],
        parameters: [
            new OA\Parameter(
                name: "iblock_code",
                description: "Код инфоблока (символьный код API)",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "catalog"
            ),
            new OA\Parameter(
                name: "element_code",
                description: "Код элемента",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "catalog"
            ),
            new OA\Parameter(
                name: "element_id",
                description: "ID элемента",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "catalog"
            ),
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Успешный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/IblockElementResponse"
        )
    )]
    #[OA\Response(
        response: "400",
        description: "Некорректный запрос",
        content: new OA\JsonContent(
            ref: "#/components/schemas/BadRequestErrorResponse"
        )
    )]
    #[OA\Response(
        response: "404",
        description: "Инфоблок не найден",
        content: new OA\JsonContent(
            ref: "#/components/schemas/NotFoundErrorResponse"
        )
    )]
    #[OA\Response(
        response: "500",
        description: "Внутренняя ошибка сервера",
        content: new OA\JsonContent(
            ref: "#/components/schemas/BadRequestErrorResponse"
        )
    )]
    /**
     * Получение информации об элементе
     * @return \Alto\MakeApi\Dto\Entity\Iblock\ElementDetailDto|void
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

    #[OA\Get(
        path: "/api/v1/iblock/{iblock_code}/tags",
        description: "Возвращает список всех уникальных тегов активных элементов инфоблока",
        summary: "Получить список тегов инфоблока", 
        tags: ["iblock"],
        parameters: [
            new OA\Parameter(
                name: "iblock_code",
                description: "Код инфоблока (символьный код API)",
                in: "path", 
                required: true,
                schema: new OA\Schema(type: "string"),
                example: "news"
            )
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Успешный запрос",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: "status",
                    type: "string",
                    example: "success"
                ),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        type: "string",
                        example: "новинка"
                    )
                ),
                new OA\Property(
                    property: "errors",
                    type: "array", 
                    example: []
                )
            ]
        )
    )]
    #[OA\Response(
        response: "404",
        description: "Инфоблок не найден",
        content: new OA\JsonContent(
            ref: "#/components/schemas/NotFoundErrorResponse"
        )
    )]
    /**
     * Получение списка тегов инфоблока
     * 
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException  
     * @throws SystemException
     */
    public function tagsAction(): array
    {
        return $this->service->getTags();
    }
}