<?php

namespace App\Core\Enum;

enum ServerHealthStatusEnum: string
{
    case HEALTHY = 'healthy';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case EXPIRED_NOT_SUSPENDED = 'expired_not_suspended';
    case EXPIRING_CRITICAL = 'expiring_critical';
    case EXPIRING_SOON = 'expiring_soon';
    case DELETED = 'deleted';
}
