<?php

namespace Alto\MakeApi\Orm\UserField;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

Loc::loadMessages(__FILE__);

class UserFieldEnumTable extends DataManager
{
    public static function getTableName()
    {
        return 'b_user_field_enum';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('USER_FIELD_ID'))
                ->configureRequired(),
            new StringField('VALUE'),
            (new EnumField('DEF'))
                ->configureRequired()
                ->configureValues([
                    'N',
                    'Y'
                ]),
            new IntegerField('SORT'),
            new StringField('XML_ID'),
        ];
    }
}
