<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\MenuDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Helper\FetcherHelper;
use Bitrix\Main\Localization\Loc;

class MenuService
{
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