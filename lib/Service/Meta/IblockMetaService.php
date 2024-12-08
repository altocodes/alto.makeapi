<?php

namespace Alto\MakeApi\Service\Meta;

use Alto\MakeApi\Dto\MetaDto;
use Alto\MakeApi\Enum\HttpStatus;
use Alto\MakeApi\Exception\Iblock\IblockException;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Iblock\InheritedProperty\SectionValues;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

class IblockMetaService extends MetaService
{
    const CACHE_DIR = '/iblock_meta';

    private int $iblockId;

    protected function __construct()
    {
        parent::__construct();

        if (!Loader::includeModule('iblock')) {
            throw IblockException::create(
                'Модуль iblock не установлен',
                'module_iblock_not_installed',
                [],
                HttpStatus::ERROR
            );
        }
    }

    public function setIblockId(int $iblockId)
    {
        $this->iblockId = $iblockId;
    }

    /**
     * Получение мета-данных для раздела инфоблока
     * @param int $id
     * @param string $url
     * @return MetaDto
     */
    public function getForSection(int $id, string $url = ''): MetaDto
    {
        $page = !empty($url) ? $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]) : [];

        $cache = Cache::createInstance();
        $cacheId = 'section_' . $id;

        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            $section = $cache->getVars();
        } else {
            $values = (new SectionValues($this->iblockId, $id))->getValues();
            $section = [
                'TITLE' => $values['SECTION_META_TITLE'] ?? '',
                'DESCRIPTION' => $values['SECTION_META_DESCRIPTION'] ?? '',
                'CANONICAL' => $url
            ];

            $cache->endDataCache($section);
        }

        return $this->resolveSettings([
            $page[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $section,
            $page[$url] ?? [],
        ]);
    }

    /**
     * Получение мета-данных для элемента инфоблока
     * @param int $id
     * @param string $url
     * @return MetaDto
     */
    public function getForElement(int $id, string $url = ''): MetaDto
    {
        $page = !empty($url) ? $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]) : [];

        $cache = Cache::createInstance();
        $cacheId = 'element_' . $id;

        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            $element = $cache->getVars();
        } else {
            $values = (new ElementValues($this->iblockId, $id))->getValues();
            $element = [
                'TITLE' => $values['ELEMENT_META_TITLE'] ?? '',
                'DESCRIPTION' => $values['ELEMENT_META_DESCRIPTION'] ?? '',
                'CANONICAL' => $url
            ];

            $cache->endDataCache($element);
        }

        return $this->resolveSettings([
            $page[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $element,
            $page[$url] ?? [],
        ]);
    }
}