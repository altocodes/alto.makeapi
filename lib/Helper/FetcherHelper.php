<?php

namespace Alto\MakeApi\Helper;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Exception\Http\BadRequestException;
use Alto\MakeApi\Service\Fetcher\FileFetcher;
use Alto\MakeApi\Service\Fetcher\ImageFetcher;
use Bitrix\Main\Context;
use Bitrix\Main\FileTable;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\MimeType;

class FetcherHelper
{
    /**
     * Получение структуры файла по ID
     * @param int $id
     * @return BaseDto|null
     */
    public static function getFileById(int $id): ?BaseDto
    {
        $file = FileTable::getById($id)->fetch();

        if (!$file) {
            return null;
        }

        if (MimeType::isImage($file['CONTENT_TYPE'])) {
            return (new ImageFetcher($file))->get();
        } else {
            return (new FileFetcher($file))->get();
        }
    }

    /**
     * Сборка ссылки на элемент инфоблока
     * @param $url
     * @param array $element
     * @return array|string|string[]|null
     */
    public static function getElementPageUrl($url, array $element)
    {
        return \CIBlock::ReplaceDetailUrl(
            $url,
            $element,
            false,
            'E'
        );
    }

    /**
     * Сборка ссылки на раздел инфоблока
     * @param $url
     * @param array $section
     * @return array|string|string[]|null
     */
    public static function getSectionPageUrl($url, array $section)
    {
        return \CIBlock::ReplaceDetailUrl(
            $url,
            $section,
            false,
            'S'
        );
    }

    /**
     * Получение меню из файла
     * @param string $type
     * @return array|false|void
     */
    public static function getMenu(string $type)
    {
        $rootDir = Context::getCurrent()->getServer()->getDocumentRoot();

        $fileMenu = $rootDir . '/.' . $type . '.menu_ext.php';
        if (!File::isFileExists($fileMenu)) {
            $fileMenu = $rootDir . '/.' . $type . '.menu.php';

            if (!File::isFileExists($fileMenu)) {
                return false;
            }
        }

        try {
            include ($fileMenu);

            if (isset($aMenuLinks) && is_array($aMenuLinks)) {
                return $aMenuLinks;
            }
        } catch (\Exception $e) {
            BadRequestException::create(Loc::getMessage('ALTO_MAKEAPI_HELPRT_EXCEPTION_MENU_NOT_INIT'));
        }
    }

    /**
     * Массовая загрузка файлов по ID с оптимизацией запросов
     * @param array $fileIds
     * @return array [fileId => BaseDto]
     */
    public static function getFilesByIds(array $fileIds): array
    {
        if (empty($fileIds)) {
            return [];
        }
        
        // Убираем дубли и сортируем для стабильного ключа кэша
        $fileIds = array_unique($fileIds);
        sort($fileIds);
        
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = 'files_batch_' . md5(implode(',', $fileIds));
        $cacheDir = '/makeapi/files/batch/';
        
        // Пытаемся получить из кэша
        if ($cache->initCache(3600, $cacheKey, $cacheDir)) {
            return $cache->getVars();
        }
        
        if (!$cache->startDataCache()) {
            return [];
        }
        
        // Регистрируем теги кэша
        $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
        $taggedCache->startTagCache($cacheDir);
        foreach ($fileIds as $fileId) {
            $taggedCache->registerTag('file_' . $fileId);
        }
        
        try {
            $files = [];
            $result = FileTable::getList([
                'filter' => ['@ID' => $fileIds],
                'cache' => ['ttl' => 3600]
            ]);
            
            while ($file = $result->fetch()) {
                $files[$file['ID']] = $file;
            }
            
            $result = [];
            foreach ($fileIds as $fileId) {
                if (isset($files[$fileId])) {
                    if (MimeType::isImage($files[$fileId]['CONTENT_TYPE'])) {
                        $result[$fileId] = (new ImageFetcher($files[$fileId]))->get();
                    } else {
                        $result[$fileId] = (new FileFetcher($files[$fileId]))->get();
                    }
                }
            }
            
            $taggedCache->endTagCache();
            $cache->endDataCache($result);
            
            return $result;
            
        } catch (\Exception $e) {
            $cache->abortDataCache();
            return [];
        }
    }
}