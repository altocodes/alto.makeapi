<?php

namespace Alto\MakeApi\Service\Fetcher;

use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;

/**
 * Получение ссылок на изображения
 */
class ImageFetcher extends BaseFetcher
{
    protected array $desktopImageSize = [
        'width' => 600,
        'height' => 600,
    ];

    protected array $mobileImageSize = [
        'width' => 300,
        'height' => 300,
    ];

    public function get(): ?PictureDto
    {
        // TODO: оргинизовать страницу настроек модуля, чтобы управлять ресайзом изображений
        if (false) {
            $desktopFileSrc = \CFile::ResizeImageGet($this->file['ID'], $this->desktopImageSize)['src'] ?? null;
            $mobileFileSrc = \CFile::ResizeImageGet($this->file['ID'], $this->mobileImageSize)['src'] ?? null;
        } else {
            $file = \CFile::GetFileArray($this->file['ID']);
            $desktopFileSrc = $file['SRC'] ?? null;
            $mobileFileSrc = $file['SRC'] ?? null;
        }




        return new PictureDto(
            $this->file['ID'],
            [
                'desktop' => $desktopFileSrc ? $this->host . $desktopFileSrc : null,
                'mobile' => $mobileFileSrc ? $this->host . $mobileFileSrc : null,
            ],
            $this->file['DESCRIPTION']
        );
    }

    public function setDesktopImageSize(array $desktopImageSize): void
    {
        $this->desktopImageSize = $desktopImageSize;
    }

    public function setMobileImageSize(array $mobileImageSize): void
    {
        $this->mobileImageSize = $mobileImageSize;
    }
}