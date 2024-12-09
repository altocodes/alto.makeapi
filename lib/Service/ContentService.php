<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Page\ContentDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Orm\ContentTable;
use Bitrix\Main\Localization\Loc;
use CFile;

class ContentService
{
    private string $siteId;

    public function __construct(string $siteId)
    {
        $this->siteId = $siteId;
    }

    public function getByCode(string $code): ContentDto
    {
        $content = ContentTable::getRow([
            'select' => ['*', 'TYPE_XML_ID' => 'TYPE.XML_ID'],
            'filter' => [
                'UF_CODE' => $code,
                'UF_SITE_ID' => [$this->siteId, ''],
            ],
            'limit' => 1,
        ]);

        if ($content) {
            return $this->prepareContent($content);
        }

        throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_CONTENT_NOT_FOUND'));
    }

    public function getByPage(string $page): array
    {
        $result = [];

        $contents = ContentTable::getList([
            'select' => ['*', 'TYPE_XML_ID' => 'TYPE.XML_ID'],
            'filter' => [
                'UF_PAGE' => $page,
                'UF_SITE_ID' => [$this->siteId, ''],
            ],
        ]);

        while($content = $contents->fetch()) {
            $result[] = $this->prepareContent($content);
        }

        return $result;
    }

    private function prepareContent(array $content): ContentDto
    {
        if ($content['TYPE_XML_ID'] == ContentTable::FILE_TYPE && !empty($content['UF_FILE'])) {
            $content['UF_CONTENT'] = CFile::GetFileArray($content['UF_FILE'])['SRC'] ?? null;
        }

        return ContentDto::fromArray($content);
    }
}