<?php

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

class Meta
{
    private $hlid;

    private $hl = [
        'NAME' => 'MetaPage',
        'TABLE_NAME' => 'alto_meta_page',
        'LANG' => [
            'en' => 'MetaPage',
            'ru' => 'Meta-данные для страниц'
        ]
    ];

    private $default = [
        'SHOW_IN_LIST' => 'Y',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
        'MULTIPLE' => 'N',
        'SHOW_FILTER' => 'E',
        'SORT' => 500,
        'MANDATORY' => 'N',
    ];

    private $fields = [
        'UF_PAGE' => [
            'USER_TYPE_ID' => 'string',
            'MANDATORY' => 'Y',
            'LABEL' => [
                'en' => 'Page url',
                'ru' => 'URL-страницы',
            ],
            'SETTINGS' => [
                'SIZE' => 50,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        'UF_TITLE' => [
            'USER_TYPE_ID' => 'string',
            'LABEL' => [
                'en' => 'Page title',
                'ru' => 'Заголовок страницы',
            ],
            'SETTINGS' => [
                'SIZE' => 50,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        'UF_DESCRIPTION' => [
            'USER_TYPE_ID' => 'string',
            'LABEL' => [
                'en' => 'Description page',
                'ru' => 'Описание страницы',
            ],
            'SETTINGS' => [
                'SIZE' => 100,
                'ROWS' => 20,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        'UF_ROBOTS' => [
            'USER_TYPE_ID' => 'string',
            'LABEL' => [
                'en' => 'Robots params',
                'ru' => 'Параметры robots',
            ],
            'SETTINGS' => [
                'SIZE' => 50,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        'UF_CANONICAL' => [
            'USER_TYPE_ID' => 'string',
            'LABEL' => [
                'en' => 'Canonical url',
                'ru' => 'Каноническая ссылка (url)',
            ],
            'SETTINGS' => [
                'SIZE' => 50,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        'UF_SITE_ID' => [
            'USER_TYPE_ID' => 'string',
            'LABEL' => [
                'en' => 'Site (LID)',
                'ru' => 'Сайт (LID)',
            ],
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
        ],
        'UF_CREATED_AT' => [
            'USER_TYPE_ID' => 'datetime',
            'LABEL' => [
                'en' => 'Date insert',
                'ru' => 'Дата добавления',
            ],
            'SETTINGS' => [
                'DEFAULT_VALUE' => [
                    'TYPE' => 'NOW',
                    'VALUE' => '',
                ],
                'USE_SECOND' => 'Y',
                'USE_TIMEZONE' => 'Y',
            ],
        ],
        'UF_UPDATED_AT' => [
            'USER_TYPE_ID' => 'datetime',
            'LABEL' => [
                'en' => 'Date update',
                'ru' => 'Дата изменения',
            ],
            'SETTINGS' => [
                'DEFAULT_VALUE' => [
                    'TYPE' => 'NOW',
                    'VALUE' => '',
                ],
                'USE_SECOND' => 'Y',
                'USE_TIMEZONE' => 'Y',
            ],
        ],
    ];

    private $permissions = [
        'everyone' => 'D',
        'RATING_VOTE' => 'D',
        'RATING_VOTE_AUTHORITY' => 'D',
    ];

    public function create()
    {
        $this->addHlBlock();
        $this->savePermission();
        $this->addFields();
    }

    public function delete()
    {
        $hl = HighloadBlockTable::getList([
            'select' => ['ID'],
            'filter' => ['TABLE_NAME' => $this->hl['TABLE_NAME']]
        ])->fetch();
        if ($hl) {
            HighloadBlockTable::delete($hl['ID']);
        }
    }

    private function addHlBlock()
    {
        $langs = $this->hl['LANG'];
        unset($this->hl['LANG']);

        $result = HighloadBlockTable::add($this->hl);
        if ($result->isSuccess()) {
            $this->hlid = $result->getId();

            foreach ($langs as $lid => $name) {
                HighloadBlockLangTable::add(
                    [
                        'ID' => $this->hlid,
                        'LID' => $lid,
                        'NAME' => $name,
                    ]
                );
            }
        }
    }

    private function savePermission()
    {
        $result = [];
        foreach ($this->permissions as $groupCode => $letter) {
            $groupId = CGroup::GetIDByCode($groupCode);
            $result[$groupId] = $letter;
        }

        foreach ($result as $groupId => $letter) {
            $taskId = CTask::GetIdByLetter($letter, 'highloadblock');

            if (!empty($taskId)) {
                HighloadBlockRightsTable::add(
                    [
                        'HL_ID' => $this->hlid,
                        'TASK_ID' => $taskId,
                        'ACCESS_CODE' => 'G' . $groupId,
                    ]
                );
            }
        }
    }

    private function addFields()
    {
        foreach ($this->fields as $key => $field) {

            $label = $field['LABEL'];
            $enums = isset($field['ENUM_VALUES']) ? $field['ENUM_VALUES'] : [];
            unset($field['LABEL']);
            unset($field['ENUM_VALUES']);

            $params = array_merge($this->default, $field);
            $params['ENTITY_ID'] = 'HLBLOCK_' . $this->hlid;
            $params['FIELD_NAME'] = $key;
            $params['XML_ID'] = $key;
            $params['EDIT_FORM_LABEL'] = $label;
            $params['LIST_COLUMN_LABEL'] = $label;
            $params['LIST_FILTER_LABEL'] = $label;

            $obUserField = new CUserTypeEntity;
            $userFieldId = $obUserField->Add($params);
            if ($userFieldId && $field['USER_TYPE_ID'] == 'enumeration') {
                $obEnum = new CUserFieldEnum();
                $obEnum->SetEnumValues($userFieldId, $enums);
            }
        }


    }
}