<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Iblock\Element\ElementDto;
use Alto\MakeApi\Dto\Iblock\Element\ElementListDto;
use Alto\MakeApi\Dto\Iblock\Property\Items\DirectoryItemDto;
use Alto\MakeApi\Dto\Iblock\Property\Items\ListItemDto;
use Alto\MakeApi\Dto\Iblock\Property\PropertyDto;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\Iblock\Section\SectionDto;
use Alto\MakeApi\Dto\Iblock\Section\SectionListDto;
use Alto\MakeApi\Dto\ListDto;
use Alto\MakeApi\Dto\PaginationDto;
use Alto\MakeApi\Dto\UserDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\FetcherHelper;
use Alto\MakeApi\Helper\IblockHelper;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Repository\IblockSectionRepository;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

Loader::includeModule('iblock');

class IblockSectionService
{
    const CACHE_TIME = 3600;
    const CACHE_DIR = '/iblock_section_repository';

    private IblockSectionRepository $repository;
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
                ? FetcherHelper::getById($section['PICTURE'])
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

    public function getSection(array $filter): SectionDto
    {
        $data = $this->repository->getSections(['filter' => $filter]);

        $item = reset($data);
        if (!$item) {
            throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_SECTION_NOT_FOUND'));
        }

        $created_by = UserTable::getById($item['CREATED_BY'])->fetch();
        $item['CREATED_BY'] = UserDto::fromArray($created_by);

        $modified_by = UserTable::getById($item['MODIFIED_BY'])->fetch();
        $item['MODIFIED_BY'] = UserDto::fromArray($modified_by);

        $item['PICTURE'] = $item['PICTURE']
            ? FetcherHelper::getById($item['PICTURE'])
            : null;

        $item['DETAIL_PICTURE'] = $item['DETAIL_PICTURE']
            ? FetcherHelper::getById($item['DETAIL_PICTURE'])
            : null;

        return SectionDto::fromArray($item);
    }

    /**
     * Получение информации о разделе по ID
     * @param int $id
     * @return SectionDto
     */
    public function getSectionById(int $id): SectionDto
    {
        return $this->getSection(['ID' => $id]);
    }

    /**
     * Получение информации о разделе по символьному коду
     * @param string $code
     * @return SectionDto
     */
    public function getSectionByCode(string $code): SectionDto
    {
        return $this->getSection(['CODE' => $code]);
    }
}