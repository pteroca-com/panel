<?php

namespace App\Core\Enum;

enum ServerStatusEnum: string
{
    case INSTALLING = 'installing';
    case RUNNING = 'running';
    case OFFLINE = 'offline';
    case STARTING = 'starting';
    case STOPPING = 'stopping';
}
