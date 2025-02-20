<?php

namespace App\Core\Enum;

enum OverwriteableCrudTemplatesEnum: string
{
    case CRUD_NEW = 'crud/new';
    case CRUD_EDIT = 'crud/edit';
    case CRUD_INDEX = 'crud/index';
    case CRUD_DETAIL = 'crud/detail';
    case CRUD_ACTION = 'crud/action';
    case CRUD_FILTERS = 'crud/filters';
    case CRUD_FORM_THEME = 'crud/form_theme';
    case CRUD_PAGINATOR = 'crud/paginator';

    public static function toArray(): array
    {
        $templates = [];
        foreach (self::cases() as $case) {
            $templates[] = $case->value;
        }
        return $templates;
    }
}
