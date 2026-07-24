<?php

namespace Alto\MakeApi\Enum;

enum FieldsType: string {

    case RANGE = 'range';
    case DIRECTORY = 'directory';
    case CHECKBOX = 'F';
    case RADIO_BUTTON = 'K';
    case SELECT = 'P';
}