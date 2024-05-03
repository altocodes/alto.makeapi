<?php

namespace Alto\MakeApi\Dto\Iblock\Property;

use Alto\MakeApi\Dto\BaseDto;
use Alto\MakeApi\Helper\IblockHelper;
use Bitrix\Iblock\ORM\ValueStorage;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class PropertyValueDto extends BaseDto
{
    private int $id;
    private string $name;
    private string $code;
    private bool $active;
    private bool $require;
    private bool $multiple;
    private string $type;
    private ?array $items;
    private $value;

    public function __construct(
        int $id,
        string $name,
        string $code,
        bool $active,
        bool $require,
        bool $multiple,
        string $type,
        ?array $items,
        $value
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->active = $active;
        $this->require = $require;
        $this->multiple = $multiple;
        $this->type = $type;
        $this->items = $items;
        $this->value = $value;
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
            $fields['NAME'],
            $fields['CODE'],
            $fields['ACTIVE'],
            $fields['IS_REQUIRED'],
            $fields['MULTIPLE'],
            $fields['PROPERTY_TYPE'],
            $fields['ITEMS'],
            $fields['VALUE']
        );
    }
}