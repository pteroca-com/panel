<?php

namespace App\Core\Enum;

enum ServerStateEnum: string
{
    case RUNNING = 'running';
    case STOPPED = 'stopped';
    case STARTING = 'starting';
    case STOPPING = 'stopping';
    case OFFLINE = 'offline';
    case SUSPENDED = 'suspended';
}
