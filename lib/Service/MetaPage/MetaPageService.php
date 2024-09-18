<?php

namespace Alto\MakeApi\Service\MetaPage;

use Alto\MakeApi\Dto\Page\MetaDto;
use Alto\MakeApi\Orm\MetaPageTable;

class MetaPageService
{
    const DEFAULT_SETTINGS_PAGE_VALUE = '/';
    const CACHE_TIME = 3600;

    public function getMetaForPage(string $url): ?MetaDto
    {
        $settings = $this->getSettingsByUrls([$url, self::DEFAULT_SETTINGS_PAGE_VALUE]);

        return $this->resolveSettings([
            $settings[self::DEFAULT_SETTINGS_PAGE_VALUE] ?? [],
            $settings[$url] ?? [],
        ]);
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

    protected function getSettingsByUrls(array $urls): array
    {
        $result = [];

        $settings = MetaPageTable::getList([
            'filter' => [
                'PAGE' => $urls,
            ],
            'cache' => [
                'ttl' => self::CACHE_TIME
            ]
        ]);

        while ($setting = $settings->fetch()) {
            $result[$setting['PAGE']] = [
                'TITLE' => $setting['TITLE'],
                'DESCRIPTION' => $setting['DESCRIPTION'],
                'ROBOTS' => $setting['ROBOTS'],
                'CANONICAL' => $setting['CANONICAL'],
            ];
        }

        return $result;
    }
}