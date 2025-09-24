<?php

namespace App\Core\DTO;

use App\Core\DTO\Collection\ServerPermissionCollection;
use App\Core\DTO\Collection\ServerVariableCollection;
use App\Core\Entity\Server;

class ServerDataDTO
{
    public function __construct(
        public array $pterodactylServer,
        public bool $isInstalling,
        public bool $isSuspended = false,
        public ?Server $server = null,
        public ?ServerPermissionCollection $serverPermissions = null,
        public ?ServerDetailsDTO $serverDetails = null,
        public ?array $dockerImages = null,
        public ?array $pterodactylClientServer = null,
        public ?array $pterodactylClientAccount = null,
        public ?array $productEggConfiguration = null,
        public ?array $availableNestEggs = null,
        public bool $hasConfigurableOptions = false,
        public bool $hasConfigurableVariables = false,
        public ?ServerVariableCollection $serverVariables = null,
        public ?array $serverBackups = null,
        public ?array $allocatedPorts = null,
        public ?array $subusers = null,
        public ?array $activityLogs = null,
        public ?array $serverSchedules = null,
    )
    {
    }
}
