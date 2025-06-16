<?php

namespace App\Core\DTO;

use App\Core\DTO\Collection\ServerVariableCollection;

class ServerDataDTO
{
    public function __construct(
        public ServerDetailsDTO $serverDetails,
        public array $pterodactylServer,
        public array $dockerImages,
        public ?array $pterodactylClientServer,
        public ?array $pterodactylClientAccount,
        public array $productEggConfiguration,
        public ?array $availableNestEggs,
        public bool $hasConfigurableOptions,
        public bool $hasConfigurableVariables,
        public ServerVariableCollection $serverVariables,
        public array $serverBackups,
        public array $allocatedPorts,
        public array $subusers,
    )
    {
    }
}
