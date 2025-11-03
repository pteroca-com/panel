<?php

namespace App\Core\Enum;

enum WidgetPosition: string
{
    case TOP = 'top';
    case LEFT = 'left';
    case RIGHT = 'right';
    case BOTTOM = 'bottom';
    case FULL_WIDTH = 'full_width';
    case NAVBAR = 'navbar';
}
