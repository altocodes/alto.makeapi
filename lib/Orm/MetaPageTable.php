<?php

namespace Alto\MakeApi\Orm;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class MetaPageTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'alto_meta_page';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            new StringField('UF_PAGE'),
            new StringField('UF_TITLE'),
            new StringField('UF_DESCRIPTION'),
            new StringField('UF_ROBOTS'),
            new StringField('UF_CANONICAL'),
            new StringField('UF_SITE_ID'),
            new DatetimeField('UF_CREATED_AT'),
            new DatetimeField('UF_UPDATED_AT'),
        ];
    }
}