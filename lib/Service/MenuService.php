<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Entity\MenuDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Helper\FetcherHelper;
use Alto\MakeApi\Helper\IblockHelper;
use Alto\MakeApi\Repository\IblockRepository;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use OpenApi\Attributes as OA;
use Throwable;

class MenuService
{
    private int $menuChildrenResolveDepth = 0;

    private const MAX_MENU_CHILDREN_DEPTH = 32;

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
            $result = [];
            foreach ($menu as $item) {
                $dto = MenuDto::fromArray($item);
                $result[] = $this->applyChildrenDirective($dto);
            }

            return $result;
        }

        throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_MENU_NOT_FOUND'));
    }

    private function applyChildrenDirective(MenuDto $item): MenuDto
    {
        $parsed = $this->parseChildrenDirective($item);
        if ($parsed === null) {
            return $item;
        }
        
        switch ($parsed['entity']) {
            case 'iblock':
                if ($parsed['scope'] === 'elements') {
                    $children = $this->loadIblockElementMenuChildren($parsed['code']);
                    if ($children !== null) {
                        $params = $item->params;
                        $params['children'] = $children;

                        return new MenuDto($item->title, $item->url, $params);
                    }
                }
                break;
            case 'menu':
                if ($this->menuChildrenResolveDepth >= self::MAX_MENU_CHILDREN_DEPTH) {
                    break;
                }
                $this->menuChildrenResolveDepth++;
                try {
                    $children = $this->loadMenuChildren($parsed['code']);
                    if ($children !== null) {
                        $params = $item->params;
                        $params['children'] = $children;

                        return new MenuDto($item->title, $item->url, $params);
                    }
                } finally {
                    $this->menuChildrenResolveDepth--;
                }
                break;
        }

        return $item;
    }

    /**
     * Директивы children:
     * - menu:{code} — вложить пункты меню с символьным кодом {code} (.type.menu.php);
     * - {iblock|hlblock}:{code}:{elements|sections} — элементы или разделы ИБ/HL.
     * Для hlblock допустим только scope elements.
     *
     * @return array{entity: string, code: string, scope: string}|null
     */
    private function parseChildrenDirective(MenuDto $item): ?array
    {
        $directive = $item->params['children'] ?? $item->params['CHILDREN'] ?? null;
        if (!is_string($directive) || $directive === '') {
            return null;
        }

        $trimmed = trim($directive);

        if (preg_match('/^menu:(.+)$/u', $trimmed, $m)) {
            $code = trim($m[1]);
            if ($code === '') {
                return null;
            }

            return [
                'entity' => 'menu',
                'code' => $code,
                'scope' => '',
            ];
        }

        if (!preg_match('/^(iblock|hlblock):([^:]+):(elements|sections)$/u', $trimmed, $m)) {
            return null;
        }

        if ($m[1] === 'hlblock' && $m[3] === 'sections') {
            return null;
        }

        return [
            'entity' => $m[1],
            'code' => $m[2],
            'scope' => $m[3],
        ];
    }

    /**
     * Пункты меню по коду файла меню (как в getByCode), с рекурсивной обработкой директив children.
     *
     * @return list<MenuDto>|null null — меню не найдено или пусто
     */
    private function loadMenuChildren(string $menuCode): ?array
    {
        $menu = FetcherHelper::getMenu($menuCode);
        if ($menu === false || !is_array($menu)) {
            return null;
        }

        if ($menu === []) {
            return [];
        }

        $children = [];
        foreach ($menu as $row) {
            $children[] = $this->applyChildrenDirective(MenuDto::fromArray($row));
        }

        return $children;
    }

    /**
     * @return list<MenuDto>|null null — не удалось загрузить
     */
    private function loadIblockElementMenuChildren(string $iblockCode): ?array
    {
        $elements = null;
        try {
            $repository = IblockRepository::factory($iblockCode);
            $elements = $repository->getElements([
                'select' => ['NAME', 'CODE', 'ID', 'IBLOCK_ID', 'IBLOCK.DETAIL_PAGE_URL'],
                'filter' => ['ACTIVE' => 'Y'],
                'sort' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ]);
            $detailUrlTemplate = $repository->getEntity()->getIblock()->fillDetailPageUrl();
            foreach ($elements as &$el) {
                $el['DETAIL_PAGE_URL'] = FetcherHelper::getElementPageUrl($detailUrlTemplate, $el);
            }
            unset($el);
        } catch (Throwable) {
            // Нет API_CODE у ИБ или ошибка ORM — обход через ElementTable.
            $elements = $this->loadIblockElementsForMenuWithoutOrm($iblockCode);
        }

        if ($elements === null) {
            return null;
        }

        $children = [];
        foreach ($elements as $el) {
            $children[] = new MenuDto(
                (string)($el['NAME'] ?? ''),
                $this->normalizeMenuUrl($el['DETAIL_PAGE_URL'] ?? ''),
                []
            );
        }

        return $children;
    }

    /**
     * Активные элементы для подменю без IblockRepository (достаточно символьного кода ИБ).
     *
     * @return list<array<string, mixed>>|null
     */
    private function loadIblockElementsForMenuWithoutOrm(string $iblockCode): ?array
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }

        $iblock = IblockHelper::getIblockByCode($iblockCode, ['ID']);
        if (!$iblock || empty($iblock['ID'])) {
            return null;
        }

        $iblockId = (int)$iblock['ID'];
        $arIBlock = \CIBlock::GetArrayByID($iblockId);
        if (!is_array($arIBlock)) {
            return null;
        }

        $urlTemplate = (string)($arIBlock['DETAIL_PAGE_URL'] ?? '');

        $rows = ElementTable::getList([
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL'],
            'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
        ])->fetchAll();

        $elements = [];
        foreach ($rows as $row) {
            $row['DETAIL_PAGE_URL'] = FetcherHelper::getElementPageUrl($urlTemplate, $row);
            $elements[] = $row;
        }

        return $elements;
    }

    private function normalizeMenuUrl(mixed $url): string
    {
        if (is_array($url)) {
            $url = reset($url);
        }

        return (string)$url;
    }
}