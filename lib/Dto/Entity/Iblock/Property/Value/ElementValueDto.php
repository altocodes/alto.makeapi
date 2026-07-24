<?php

namespace Alto\MakeApi\Dto\Entity\Iblock\Property\Value;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Dto\Entity\Iblock\PictureDto;

use Alto\MakeApi\Helper\FetcherHelper;

class ElementValueDto extends BaseDto
{
    public readonly int $id;
    public readonly string $name;
    public readonly string $code;
    public readonly ?string $description;
    public readonly ?PictureDto $picture;
    public readonly ?PictureDto $detailPicture; 

    /**
     * @param int $id
     * @param string $name
     * @param string $code
     */
    public function __construct(
        int    $id,
        string $name,
        string $code,
        ?string $description,
        ?PictureDto $picture,
        ?PictureDto $detailPicture
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->picture = $picture;
        $this->detailPicture = $detailPicture;
    }

    public static function fromArray(array $fields): self
    {
        return new self(
            (int)$fields['ID'],
            $fields['NAME'],
            $fields['CODE'],
            $fields['DESCRIPTION'],
            $fields['PICTURE'] ? FetcherHelper::getFileById($fields['PICTURE']) : null,
            $fields['DETAIL_PICTURE'] ? FetcherHelper::getFileById($fields['DETAIL_PICTURE']) : null
        );
    }
}