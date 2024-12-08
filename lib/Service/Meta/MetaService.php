<?php

namespace Alto\MakeApi\Service\Meta;

use Alto\MakeApi\Dto\MetaDto;
use Alto\MakeApi\Orm\MetaPageTable;
use Bitrix\Main\Context;
use Bitrix\Main\SiteTable;

class MetaService
{
    const DEFAULT_SETTINGS_PAGE_VALUE = '/';
    const CACHE_TIME = 3600;

    protected static $instance;
    protected string $siteId;

    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function setSiteId(string $siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * Получение мета-данных по url страницы
     * @param string $url
     * @return MetaDto|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getForPage(string $url): ?MetaDto
    {
        $settings = $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]);

        if (empty($settings)) {
            $settings = $this->getDefault();
        }

        return $this->resolveSettings([
            $settings[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $settings[$url] ?? [],
        ]);
    }

    protected function getSettingsByUrls(array $urls): array
    {
        $result = [];

        $settings = MetaPageTable::getList([
            'filter' => [
                'UF_PAGE' => $urls,
                [
                    'LOGIC' => 'OR',
                    ['UF_SITE_ID' => ''],
                    ['UF_SITE_ID' => $this->siteId]
                ]
            ],
            'cache' => [
                'ttl' => self::CACHE_TIME
            ]
        ]);

        while ($setting = $settings->fetch()) {
            $result[$setting['UF_PAGE']] = [
                'TITLE' => $setting['UF_TITLE'],
                'DESCRIPTION' => $setting['UF_DESCRIPTION'],
                'ROBOTS' => $setting['UF_ROBOTS'],
                'CANONICAL' => $setting['UF_CANONICAL'],
            ];
        }

        return $result;
    }

    /**
     * Определяет параметры мета тегов с учетом приоритетов
     * @param array $settings массив параметров упорядоченных от меньшего приоритета к большому
     * @return MetaDto
     */
    protected function resolveSettings(array $settings): MetaDto
    {
        $result = [];
        foreach ($settings as $setting) {
            $result = array_replace($result, array_filter($setting));
        }

        return MetaDto::fromArray($result);
    }

    /**
     * Мета-данные по умолчанию
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getDefault()
    {
        $settings = [];
        $site = SiteTable::getList([
            'select' => ['SITE_NAME'],
            'filter' => ['LID' => $this->siteId],
            'cache' => ['ttl' => self::CACHE_TIME]
        ])->fetch();

        if ($site) {
            $settings[self::DEFAULT_SETTINGS_PAGE_VALUE] = [
                'TITLE' => $site['SITE_NAME'],
                'ROBOTS' => 'index, follow'
            ];
        }

        return $settings;
    }

    protected function __construct()
    {
        $this->siteId = Context::getCurrent()->getSite();
    }
}