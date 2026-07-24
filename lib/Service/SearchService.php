<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Entity\Iblock\Element\ElementShortDto;
use Alto\MakeApi\Dto\Entity\PaginationDto;
use Alto\MakeApi\Dto\Response\SearchResultDto;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\IblockHelper;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Repository\CatalogSectionRepository;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Alto\MakeApi\Service\Catalog\CatalogService;
use OpenApi\Attributes as OA;
use CSearch;

class SearchService
{
    const SEARCH_MAX_LIMIT = 20;
    const SEARCH_TYPE_PRODUCT = 'product';
    const SEARCH_TYPE_SECTION = 'section';

    private CatalogService $catalogService;
    private CatalogSectionRepository $catalogSectionRepository;
    private int $productsIblockId;

    public function __construct()
    {
        if (!Loader::includeModule('search')) {
            throw new LoaderException(Loc::getMessage('ALTO_MAKEAPI_MODULE_SEARCH_NOT_INSTALL'));
        }
    }

    private function initCatalog()
    {
        $productsIblock = IblockHelper::getIblockByCode(CatalogService::CATALOG_CODE, ['ID']);
        if (!$productsIblock) {
            throw new RepositoryException('Инфоблок товаров не найден');
        }
        $this->productsIblockId = $productsIblock['ID'];

        $this->catalogService = new CatalogService();
        $this->catalogSectionRepository = new CatalogSectionRepository(CatalogService::CATALOG_CODE);
    } 

    #[OA\Schema(
        schema: "SearchResponse",
        properties: [
            new OA\Property(
                property: "status",
                type: "string",
                example: "success"
            ),
            new OA\Property(
                property: "data",
                ref: "#/components/schemas/SearchResult"
            ),
            new OA\Property(
                property: "errors",
                type: "array",
                items: new OA\Items(type: "string"),
                example: []
            )
        ]
    )]
    public function getResult(
        string $query,
        array $types,
        int $page = 1,
        int $limit = 20,
        ?string $sort = null,
        string $order = 'ASC'
    ): SearchResultDto {
        $result = [];
        $pagination = null;

        // Если пользователь запросил товары или категории - инициализируем каталог
        if(in_array('products', $types)) {
            $this->initCatalog();
        }

        foreach ($types as $type) {

            switch ($type) {
                case 'products':
                    $items = $this->searchInCatalog($query, $page, $limit, $sort ?: 'rank', $order);

                    $select = [
                        'ID',
                        'NAME',
                        'CODE',
                        'DETAIL_TEXT',
                        'PREVIEW_TEXT',
                        'PROPERTY_PHOTOS',
                    ];

                    $result['products'] = $this->catalogService->getProducts(['ID' => $items['products']], $page, $limit, $sort, $order, $select);
                    $result['categories'] = $this->catalogSectionRepository->getSectionsById($items['categories']);

                    break;
                default:
                    $items = $this->searchInIblock($type, $query, $page, $limit, $sort ?: 'rank', $order);

                    $repo = IblockRepository::factory($type);
                    $elements = $repo->getElements([
                        'filter' => ['ID' => $items[$type]],
                        'limit' => $limit,
                        'offset' => $page
                    ]);

                    $items = [];
                    foreach($elements as $element) {
                        $items[] = ElementShortDto::fromArray($element);
                    }

                    $result[$type] = $items;
            }
        }

        // TODO: пагинация
        return new SearchResultDto($result, $pagination);
    }


    private function searchInCatalog($query, int $page = 1, int $limit = 10, string $sort = 'rank', string $order = 'desc')
    {
        $result = $this->search('iblock', $this->productsIblockId, $query, $page, $limit, [$sort => $order], true);
        $itemIds = array_column($result['items'], 'ITEM_ID');

        $productIds = [];
        $categoryIds = [];
        foreach ($itemIds as $id) {
            // Разделы имеют префикс "S", элементы - нет
            if (strpos($id, 'S') !== 0) {
                $productIds[] = (int)$id;
            } else {
                $categoryIds[] = (int)substr($id, 1);
            }
        }

        return [
            'products' => $productIds,
            'categories' => $categoryIds
        ];
    }

    private function searchInIblock(string $iblock_code, $query, int $page = 1, int $limit = 10, string $sort = 'rank', string $order = 'desc')
    {
        $iblock = IblockHelper::getIblockByCode($iblock_code, ['ID']);
        if ($iblock) {
            $result = $this->search('iblock', $iblock['ID'], $query, $page, $limit, [$sort => $order], true);
            $itemIds = array_column($result['items'], 'ITEM_ID');

            $elementIds = [];
            foreach ($itemIds as $id) {
                // Разделы имеют префикс "S", элементы - нет
                if (strpos($id, 'S') !== 0) {
                    $elementIds[] = (int)$id;
                }
            }

            return [
                $iblock_code => $elementIds
            ];
        }

        return [
            $iblock_code => []
        ];
    }


    /**
     * Универсальный метод поиска через модуль поиска Bitrix
     */
    private function search(
        string $module, 
        int $param2, 
        string $query, 
        int $page = 1, 
        int $limit = 10, 
        array $sort = [],
        bool $usePagination = true
    ): array
    {
        $search = new CSearch();
        $search->SetOptions(['ERROR_ON_EMPTY_STEM' => false]);
        
        $params = [
            'QUERY' => $query,
            'SITE_ID' => SITE_ID,
            'MODULE_ID' => $module,
            'PARAM2' => $param2,
        ];

        $search->Search($params, $sort, [
            'STEMMING' => false,
            'WHERE' => [
                'ACTIVE' => 'Y',
            ]
        ]);
        
        // Если используется пагинация - применяем навигацию
        if ($usePagination) {
            $search->NavStart($limit, false, $page);
        } else {
            // Без пагинации - ограничиваем максимальное количество
            $search->NavStart(self::SEARCH_MAX_LIMIT, false, 1);
        }

        $result = [];
        $totalCount = 0;
        
        while ($item = $search->fetch()) {
            $result[] = $item;
        }
        
        if ($usePagination) {
            $totalCount = $search->SelectedRowsCount();
        }

        return [
            'items' => $result,
            'total_count' => $usePagination ? $totalCount : count($result)
        ];
    }
}