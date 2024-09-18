<?php

namespace Alto\MakeApi\Service\Fetcher;

use Alto\MakeApi\Dto\Iblock\FileDto;

/**
 * Получение ссылки на файл
 */
class FileFetcher extends BaseFetcher
{
    public function get(): ?FileDto
    {
        return new FileDto(
            $this->file['ID'],
            $this->host . '/upload/' . $this->file['SUBDIR'] . '/' . $this->file['FILE_NAME'],
            $this->file['ORIGINAL_NAME'],
            $this->file['DESCRIPTION']
        );
    }
}