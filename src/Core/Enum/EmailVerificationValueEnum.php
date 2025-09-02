<?php

namespace App\Core\Enum;

enum EmailVerificationValueEnum: string
{
    case DISABLED = 'disabled';
    case OPTIONAL = 'optional';
    case REQUIRED = 'required';
}
