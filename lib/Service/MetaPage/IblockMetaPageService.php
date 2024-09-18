<?php

namespace Alto\MakeApi\Service\MetaPage;

use Alto\MakeApi\Dto\Page\MetaDto;
use Alto\MakeApi\Enum\HttpStatus;
use Alto\MakeApi\Exception\Iblock\IblockException;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Iblock\InheritedProperty\SectionValues;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

class IblockMetaPageService extends MetaPageService
{
    const CACHE_DIR = '/iblock_meta';

    private int $iblockId;

    public function __construct(int $iblockId)
    {
        if (!Loader::includeModule('iblock')) {
            throw IblockException::create(
                'Модуль iblock не установлен',
                'module_iblock_not_installed',
                [],
                HttpStatus::ERROR
            );
        }

        $this->iblockId = $iblockId;
    }

    public function getBySectionId(int $sectionId, string $url): ?MetaDto
    {
        $settings = $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]);

        $iblockSetting = $this->getSectionSetting($sectionId);

        return $this->resolveSettings([
            $settings[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $iblockSetting,
            $settings[$url] ?? [],
        ]);
    }

    private function getSectionSetting(int $id): array
    {
        $cache = Cache::createInstance();
        $cacheId = "section_$id";

        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            $setting = $cache->getVars();
        } else {
            $values = (new SectionValues($this->iblockId, $id))->getValues();
            $setting = [
                'TITLE' => $values['SECTION_META_TITLE'] ?? '',
                'DESCRIPTION' => $values['SECTION_META_DESCRIPTION'] ?? '',
            ];

            $cache->endDataCache($setting);
        }

        return $setting;
    }

    public function getByElementId(int $elementId, string $url): ?MetaDto
    {
        $settings = $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]);

        $iblockSetting = $this->getElementSetting($elementId);

        return $this->resolveSettings([
            $settings[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $iblockSetting,
            $settings[$url] ?? [],
        ]);
    }

    private function getElementSetting(int $id): array
    {
        $cache = Cache::createInstance();
        $cacheId = "element_$id";

        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            $setting = $cache->getVars();
        } else {
            $values = (new ElementValues($this->iblockId, $id))->getValues();
            $setting = [
                'TITLE' => $values['ELEMENT_META_TITLE'] ?? '',
                'DESCRIPTION' => $values['ELEMENT_META_DESCRIPTION'] ?? '',
            ];

            $cache->endDataCache($setting);
        }

        return $setting;
    }
}