<?php

namespace Alto\MakeApi\Dto\Iblock;

use Alto\MakeApi\Dto\BaseDto;

class ElementShortDto extends BaseDto
{
    private int $id;
    private ?string $code;
    private string $name;
    private ?string $url;
    private int $sort;
    private string $previewText;
    private ?PictureDto $previewPicture;

    public function __construct(
        int $id,
        ?string $code,
        string $name,
        ?string $url,
        int $sort,
        string $previewText,
        ?PictureDto $previewPicture,
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->url = $url;
        $this->sort = $sort;
        $this->previewText = $previewText;
        $this->previewPicture = $previewPicture;

    }

    /**
     * Инициализация из массива
     *
     * @param array $fields
     * @return self
     */
    public static function fromArray(array $fields): self
    {
        return new self(
            $fields['ID'],
            $fields['CODE'],
            $fields['NAME'],
            $fields['DETAIL_PAGE_URL'],
            $fields['SORT'],
            $fields['PREVIEW_TEXT'],
            PictureDto::byId($fields['PREVIEW_PICTURE']),
        );
    }
}