<?php

namespace App\Core\DTO;

class ServerDataDTO
{
    public function __construct(
        public ServerDetailsDTO $serverDetails,
        public array $pterodactylServer,
        public array $dockerImages,
        public array $pterodactylClientServer,
        public array $pterodactylClientAccount,
        public array $productEggConfiguration,
        public ?array $availableNestEggs,
        public bool $hasConfigurableOptions,
        public bool $hasConfigurableVariables,
    )
    {
    }
}
