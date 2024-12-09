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
}