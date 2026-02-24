<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Entity\MenuDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Helper\FetcherHelper;
use Bitrix\Main\Localization\Loc;
use OpenApi\Attributes as OA;

class MenuService
{
    #[OA\Schema(
        schema: "MenuResponse",
        properties: [
            new OA\Property(
                property: "status",
                description: "Статус ответа",
                type: "string",
                example: "success"
            ),
            new OA\Property(
                property: "data",
                description: "Данные ответа",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/MenuDto")
            ),
            new OA\Property(
                property: "errors",
                description: "Ошибки ответа",
                type: "array",
                example: []
            )
        ]
    )]
    public function getByCode(string $type): array
    {
        if ($menu = FetcherHelper::getMenu($type)) {

            foreach ($menu as &$item) {
                $item = MenuDto::fromArray($item);
            }
            unset($item);

            return $menu;
        }

        throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_MENU_NOT_FOUND'));
    }
}