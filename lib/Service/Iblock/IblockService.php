<?php

namespace Alto\MakeApi\Service\Iblock;

use Alto\MakeApi\Dto\Iblock\Element\ElementDto;
use Alto\MakeApi\Dto\Iblock\Element\ElementListDto;
use Alto\MakeApi\Dto\Iblock\ElementDetailDto;
use Alto\MakeApi\Dto\Iblock\Property\Items\DirectoryItemDto;
use Alto\MakeApi\Dto\Iblock\Property\Items\ListItemDto;
use Alto\MakeApi\Dto\Iblock\Property\PropertyDto;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\Iblock\Section\SectionListDto;
use Alto\MakeApi\Dto\ListDto;
use Alto\MakeApi\Dto\MetaDto;
use Alto\MakeApi\Dto\PaginationDto;
use Alto\MakeApi\Dto\UserDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\FetcherHelper;
use Alto\MakeApi\Helper\IblockHelper;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\Meta\IblockMetaService;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

Loader::includeModule('iblock');

class IblockService
{
    const CACHE_TIME = 1;
    const CACHE_DIR = '/iblock_repository';

    private IblockRepository $repository;
    private IblockMetaService $meta;
    protected Cache $cache;
    protected $taggedCache;

    public function __construct(string $code)
    {
        try {
            $this->repository = IblockRepository::factory($code);
        } catch (RepositoryException $e) {
            throw NotFoundException::create($e->getMessage());
        }

        $this->cache = Cache::createInstance();
        $this->taggedCache = Application::getInstance()->getTaggedCache();
        $this->meta = IblockMetaService::getInstance();

        $this->meta->setIblockId($this->repository->getIblockId());
    }

    /**
     * Получение информации об инфоблоке
     *
     * @return IblockDto
     */
    public function getInfo(): IblockDto
    {
        $cacheKey = md5(__METHOD__ . $this->repository->getIblockId() . 'getIblock');

        if ($this->cache->initCache(self::CACHE_TIME, $cacheKey, self::CACHE_DIR)) {
            $result = $this->cache->getVars();
        } else {
            $this->cache->startDataCache();
            $this->taggedCache->startTagCache(self::CACHE_DIR);

            $result = $this->repository->getIblock();
            if (isset($result['PROPERTIES'])) {
                foreach ($result['PROPERTIES'] as &$property) {
                    if (isset($property['ITEMS'])) {
                        if ($property['PROPERTY_TYPE'] === PropertyTable::TYPE_LIST) {
                            $property['ITEMS'] = array_map(fn($e): ListItemDto => ListItemDto::fromArray($e), $property['ITEMS']);
                        } else {
                            $property['ITEMS'] = array_map(function ($e) {
                                if ($e['UF_FILE']) {
                                    $e['UF_FILE'] = FetcherHelper::getFileById($e['UF_FILE']);
                                }

                                return DirectoryItemDto::fromArray($e);
                            }, $property['ITEMS']);
                        }
                    }

                    $property = PropertyDto::fromArray($property);
                }
                unset($property);
            }

            $this->taggedCache->registerTag('iblock_info_id_' . $this->repository->getIblockId());
            $this->taggedCache->endTagCache();
            $this->cache->endDataCache($result);
        }

        return IblockDto::fromArray($result);
    }

    /**
     * Получение элементов
     *
     * @param array $filter
     * TODO: добавить выбираемые поля
     * @param int $page
     * @param int $limit
     * @param string $sort
     * @param string $order
     * @return ListDto
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getList(
        array $filter = [],
        int $page = 1,
        int $limit = 10,
        string $sort = IblockRepository::SORT_BY_DEFAULT,
        string $order = IblockRepository::SORT_ORDER_DEFAULT
    ): ListDto
    {
        $params = [
            'filter' => IblockHelper::prepareFilter($filter),
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'sort' => [$sort => $order],
        ];

        $cacheKey = md5(__METHOD__ . $this->repository->getIblockId() . serialize($params));

        if ($this->cache->initCache(self::CACHE_TIME, $cacheKey, self::CACHE_DIR)) {
            $result = $this->cache->getVars();
        } else {
            $this->cache->startDataCache();
            $this->taggedCache->startTagCache(self::CACHE_DIR);

            $elements = $this->repository->getElements($params);

            foreach ($elements as &$element) {

                $element['PREVIEW_PICTURE'] = $element['PREVIEW_PICTURE']
                    ? FetcherHelper::getFileById($element['PREVIEW_PICTURE'])
                    : null;

                $element['DETAIL_PICTURE'] = $element['DETAIL_PICTURE']
                    ? FetcherHelper::getFileById($element['DETAIL_PICTURE'])
                    : null;


                foreach ($element['PROPERTIES'] as $code => $value) {
                    if ($property = $this->repository->getProperty($code)) {
                        $element['PROPERTIES'][$code] = IblockHelper::parseValue($property, $value);
                    }
                }

                if ($section = SectionTable::getById($element['IBLOCK_SECTION_ID'])->fetch()) {
                    $section['PICTURE'] = $section['PICTURE']
                        ? FetcherHelper::getFileById($section['PICTURE'])
                        : null;

                    $element['IBLOCK_SECTION_ID'] = SectionListDto::fromArray($section);
                }

                if (!$element['IBLOCK_SECTION_ID']) {
                    unset($element['IBLOCK_SECTION_ID']);
                }

                $element = ElementListDto::fromArray($element);
            }
            unset($element);

            $nav = $this->repository->getNavigation();

            $pagination = new PaginationDto(
                $nav->getCurrentPage(),
                $nav->getPageCount(),
                $nav->getLimit(),
                $nav->getRecordCount()
            );

            $result = new ListDto($pagination, $elements);

            $this->taggedCache->registerTag('iblock_elements_id_' . $this->repository->getIblockId());
            $this->taggedCache->endTagCache();
            $this->cache->endDataCache($result);
        }

        return $result;
    }

    /**
     * Получение элемента по фильтру
     * @param array $filter
     * @return ElementDetailDto
     * @throws \Alto\MakeApi\Exception\Http\BaseHttpException
     */
    public function getElement(array $filter): ElementDetailDto
    {
        $cacheKey = md5(__METHOD__ . $this->repository->getIblockId() . serialize($filter));

        if ($this->cache->initCache(self::CACHE_TIME, $cacheKey, self::CACHE_DIR)) {
            $item = $this->cache->getVars();
        } else {
            $this->cache->startDataCache();
            $this->taggedCache->startTagCache(self::CACHE_DIR);

            // TODO: изменить на отдельный запрос
            $data = $this->repository->getElements(['filter' => $filter]);

            $item = reset($data);
            if (!$item) {
                throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_ELEMENT_NOT_FOUND'));
            }

            $created_by = UserTable::getById($item['CREATED_BY'])->fetch();
            $item['CREATED_BY'] = UserDto::fromArray($created_by);

            $modified_by = UserTable::getById($item['MODIFIED_BY'])->fetch();
            $item['MODIFIED_BY'] = UserDto::fromArray($modified_by);

            $item['PREVIEW_PICTURE'] = $item['PREVIEW_PICTURE']
                ? FetcherHelper::getFileById($item['PREVIEW_PICTURE'])
                : null;

            $item['DETAIL_PICTURE'] = $item['DETAIL_PICTURE']
                ? FetcherHelper::getFileById($item['DETAIL_PICTURE'])
                : null;

            $item['DETAIL_PAGE_URL'] = FetcherHelper::getElementPageUrl($this->repository->getEntity()->getIblock()->fillDetailPageUrl(), $item);

            foreach ($item['PROPERTIES'] as $code => $value) {
                if ($property = $this->repository->getProperty($code)) {
                    $item['PROPERTIES'][$code] = IblockHelper::parseValue($property, $value);
                }
            }

            $this->taggedCache->registerTag('iblock_element_id_' . $this->repository->getIblockId() . '_' . $item['ID']);
            $this->taggedCache->endTagCache();
            $this->cache->endDataCache($item);
        }

        $meta = $this->meta->getForElement($item['ID'], $item['DETAIL_PAGE_URL']);

        return new ElementDetailDto(
            ElementDto::fromArray($item),
            $meta
        );
    }

    /**
     * Получение элемента по ID
     * @param int $id
     * @return ElementDetailDto
     * @throws \Alto\MakeApi\Exception\Http\BaseHttpException
     */
    public function getElementById(int $id): ElementDetailDto
    {
        return $this->getElement(['ID' => $id]);
    }

    /**
     * Получение элемента по коду
     * @param string $code
     * @return ElementDetailDto
     * @throws \Alto\MakeApi\Exception\Http\BaseHttpException
     */
    public function getElementByCode(string $code): ElementDetailDto
    {
        return $this->getElement(['CODE' => $code]);
    }
}