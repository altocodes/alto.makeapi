<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Entity\Iblock\SectionDetailDto;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\Iblock\IblockSectionService;
use Bitrix\Main\Engine\Action;
use Alto\MakeApi\Dto\Entity\ListDto;
use OpenApi\Attributes as OA;

class IblockSectionController extends BaseController
{
    private IblockSectionService $service;

    protected function processBeforeAction(Action $action)
    {
        $this->service = new IblockSectionService($this->request->get('iblock_code'));

        return parent::processBeforeAction($action);
    }

    #[OA\Get(
        path: "/api/v1/iblock/{iblock_code}/sections",
        description: "Возвращает пагинированный список разделов инфоблока Bitrix",
        summary: "Получить список разделов инфоблока",
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
                description: "Поле для сортировки (ID, NAME, SORT и т.д.)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "NAME"
            ),
            new OA\Parameter(
                name: "order",
                description: "Направление сортировки (asc, desc)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"]),
                example: "asc"
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Успешный запрос",
        content: new OA\JsonContent(ref: "#/components/schemas/SectionListResponse")
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
    public function listAction(): ListDto
    {
        // TODO: валидация

        $page = $this->request->get('page') ?? 1;
        $limit = $this->request->get('limit') ?? 10;
        $sort = $this->request->get('sort') ?? IblockRepository::SORT_BY_DEFAULT;
        $order = $this->request->get('order') ?? IblockRepository::SORT_ORDER_DEFAULT;

        return $this->service->getSections($page, $limit, $sort, $order);
    }

    #[OA\Get(
        path: "/api/v1/iblock/{iblock_code}/section",
        description: "Возвращает данные раздела инфоблока Bitrix",
        summary: "Получить раздел инфоблока",
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
                name: "section_code",
                description: "Код раздела",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                example: "electronics"
            ),
            new OA\Parameter(
                name: "section_id",
                description: "ID раздела",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                example: 123
            ),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Успешный запрос",
        content: new OA\JsonContent(ref: "#/components/schemas/SectionDetailResponse")
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
    public function sectionAction(): SectionDetailDto
    {
        if ($section_code = $this->request->get('section_code')) {
            return $this->service->getSectionByCode($section_code);
        } elseif ($section_id = $this->request->get('section_id')) {
            return $this->service->getSectionById($section_id);
        }
    }
}