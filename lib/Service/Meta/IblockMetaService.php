<?php

namespace Alto\MakeApi\Service\Meta;

use Alto\MakeApi\Dto\Entity\MetaDto;
use Alto\MakeApi\Enum\HttpStatus;
use Bitrix\Iblock\InheritedProperty\IblockValues;
use Alto\MakeApi\Exception\Iblock\IblockException;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Iblock\InheritedProperty\SectionValues;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;

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
                'CANONICAL' => $url,
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
    public function getForElement(int $id, string $robots = '', string $url = ''): MetaDto
    {
        $page = !empty($url) ? $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]) : [];

        $cache = Cache::createInstance();
        $cacheId = 'element_' . $id;

        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            $element = $cache->getVars();
        } else {
            $values = (new ElementValues($this->iblockId, $id))->getValues();

            // Получаем данные элемента для подстановки по умолчанию
            $elementData = $this->getElementData($id);

            // Если у элемента отсутствует заполненный SEO, берем поля NAME и PREVIEW_TEXT элемента (очистив предварительно)
            $element = [
                'TITLE' => $values['ELEMENT_META_TITLE'] ?? $elementData['NAME'] ?? '',
                'DESCRIPTION' => !empty($values['ELEMENT_META_DESCRIPTION']) 
                    ? $values['ELEMENT_META_DESCRIPTION'] 
                    : (!empty($elementData['PREVIEW_TEXT']) 
                        ? trim(preg_replace('/\s+/', ' ', strip_tags($elementData['PREVIEW_TEXT'])))
                        : ''),
                'ROBOTS' => !empty($robots) ? $robots : 'index, follow',
                'CANONICAL' => $url,
                'H1' => $values['ELEMENT_PAGE_TITLE'] ?? $elementData['NAME'] ?? '',
                'KEYWORDS' => $values['ELEMENT_META_KEYWORDS'] ?? null
            ];

            $cache->endDataCache($element);
        }

        return $this->resolveSettings([
            $page[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $element,
            $page[$url] ?? [],
        ]);
    }

    /**
     * Получение meta-данных для инфоблока
     */
    public function getForIblock(): MetaDto
    {
        $cache = Cache::createInstance();
        $cacheId = 'iblock_' . $this->iblockId;

        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            $iblockMeta = $cache->getVars();
        } else {
            // Правильный способ получения SEO-данных инфоблока
            $iblockValues = new IblockValues($this->iblockId);
            $values = $iblockValues->getValues();
            
            $iblockMeta = [
                'TITLE' => $values['IBLOCK_META_TITLE'] ?? '',
                'DESCRIPTION' => $values['IBLOCK_META_DESCRIPTION'] ?? '',
                'KEYWORDS' => $values['IBLOCK_META_KEYWORDS'] ?? '',
                'H1' => $values['IBLOCK_PAGE_TITLE'] ?? 'Каталог',
            ];

            $cache->endDataCache($iblockMeta);
        }

        // Получаем настройки для страницы каталога
        $pageSettings = $this->getSettingsByUrls(['/catalog/', self::DEFAULT_SETTINGS_PAGE_VALUE]);

        return $this->resolveSettings([
            $pageSettings[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $iblockMeta,
            $pageSettings['/catalog/'] ?? [],
        ]);
    }

    /**
     * Получение данных элемента
     * @param int $id
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getElementData(int $id): array
    {
        $cache = Cache::createInstance();
        $cacheId = 'element_data_' . $this->iblockId . '_' . $id;
        
        if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR)) {
            return $cache->getVars();
        }
        
        $element = ElementTable::getList([
            'select' => [
                'ID',
                'NAME',
                'PREVIEW_TEXT',
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID'
            ],
            'filter' => [
                'ID' => $id,
                'IBLOCK_ID' => $this->iblockId,
            ],
            'cache' => [
                'ttl' => self::CACHE_TIME
            ]
        ])->fetch();
        
        if ($element) {
            $cache->startDataCache();
            $cache->endDataCache($element);
            return $element;
        }
        
        return [];
    }
}