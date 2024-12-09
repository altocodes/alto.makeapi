<?php

namespace Alto\MakeApi\Service\Iblock;

use Alto\MakeApi\Dto\Iblock\Section\SectionDto;
use Alto\MakeApi\Dto\Iblock\Section\SectionListDto;
use Alto\MakeApi\Dto\Iblock\SectionDetailDto;
use Alto\MakeApi\Dto\ListDto;
use Alto\MakeApi\Dto\PaginationDto;
use Alto\MakeApi\Dto\UserDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\FetcherHelper;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Repository\IblockSectionRepository;
use Alto\MakeApi\Service\Meta\IblockMetaService;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

Loader::includeModule('iblock');

class IblockSectionService
{
    const CACHE_TIME = 3600;
    const CACHE_DIR = '/iblock_section_repository';

    private IblockSectionRepository $repository;
    protected IblockMetaService $meta;
    protected Cache $cache;
    protected $taggedCache;

    public function __construct(string $code)
    {
        try {
            $this->repository = IblockSectionRepository::factory($code);
        } catch (RepositoryException $e) {
            throw NotFoundException::create($e->getMessage());
        }

        $this->cache = Cache::createInstance();
        $this->taggedCache = Application::getInstance()->getTaggedCache();
        $this->meta = IblockMetaService::getInstance();
    }

    public function getSections(
        int $page = 0,
        int $limit = 10,
        string $sort = IblockRepository::SORT_BY_DEFAULT,
        string $order = IblockRepository::SORT_ORDER_DEFAULT
    ): ListDto
    {

        $sections = $this->repository->getSections([
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'sort' => [$sort => $order]
        ]);

        foreach ($sections as &$section) {
            $section['PICTURE'] = $section['PICTURE']
                ? FetcherHelper::getFileById($section['PICTURE'])
                : null;

            $section = SectionListDto::fromArray($section);
        }
        unset($section);

        $nav = $this->repository->getNavigation();

        $pagination = new PaginationDto(
            $nav->getCurrentPage(),
            $nav->getPageCount(),
            $nav->getLimit(),
            $nav->getRecordCount()
        );

        $result = new ListDto($pagination, $sections);

        return $result;
    }

    public function getSection(array $filter): SectionDetailDto
    {
        $data = $this->repository->getSections([
            'select' => ['*', 'IBLOCK.SECTION_PAGE_URL'],
            'filter' => $filter
        ]);

        $item = reset($data);
        if (!$item) {
            throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_SECTION_NOT_FOUND'));
        }

        $created_by = UserTable::getById($item['CREATED_BY'])->fetch();
        $item['CREATED_BY'] = UserDto::fromArray($created_by);

        $modified_by = UserTable::getById($item['MODIFIED_BY'])->fetch();
        $item['MODIFIED_BY'] = UserDto::fromArray($modified_by);

        $item['PICTURE'] = $item['PICTURE']
            ? FetcherHelper::getFileById($item['PICTURE'])
            : null;

        $item['DETAIL_PICTURE'] = $item['DETAIL_PICTURE']
            ? FetcherHelper::getFileById($item['DETAIL_PICTURE'])
            : null;

        $item['URL'] = FetcherHelper::getSectionPageUrl($item['IBLOCK']['SECTION_PAGE_URL'], $item);

        $this->meta->setIblockId($item['IBLOCK_ID']);
        $meta = $this->meta->getForSection($item['ID'], $item['URL']);

        return new SectionDetailDto(
            SectionDto::fromArray($item),
            $meta
        );
    }

    /**
     * Получение информации о разделе по ID
     * @param int $id
     * @return SectionDetailDto
     */
    public function getSectionById(int $id): SectionDetailDto
    {
        return $this->getSection(['ID' => $id]);
    }

    /**
     * Получение информации о разделе по символьному коду
     * @param string $code
     * @return SectionDetailDto
     */
    public function getSectionByCode(string $code): SectionDetailDto
    {
        return $this->getSection(['CODE' => $code]);
    }
}