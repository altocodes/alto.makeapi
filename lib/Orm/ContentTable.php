<?php

namespace Alto\MakeApi\Orm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

Loc::loadMessages(__FILE__);

/**
 * Class ContentTable
 * TODO: возможно вынесется в репозиторий HL-блока
 */
class ContentTable extends DataManager
{
    /**
     * Типы контента
     */
    const TEXT_TYPE = 'text';
    const FILE_TYPE = 'file';

    public static function getTableName()
    {
        return 'alto_content';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new StringField('UF_CODE'))
                ->configureRequired(),
            (new EnumField('UF_TYPE'))
                ->configureRequired()
                ->configureValues([
                    self::TEXT_TYPE => 'text',
                    self::FILE_TYPE => 'file'
                ]),
            new StringField('UF_CONTENT'),
            new StringField('UF_SITE_ID'),
            new IntegerField('UF_SORT'),
            new IntegerField('UF_FILE'),
            new DatetimeField('UF_CREATED_AT'),
            new DatetimeField('UF_UPDATED_AT'),
        ];
    }
}
