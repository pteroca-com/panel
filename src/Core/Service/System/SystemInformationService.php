<?php

namespace App\Core\Service\System;

use App\Core\Service\Pterodactyl\PterodactylService;
use Doctrine\ORM\EntityManagerInterface;

readonly class SystemInformationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PterodactylService $pterodactylService,
    )
    {
    }

    public function getSystemInformation(): array
    {
        return [
            'php' => [
                'version' => phpversion(),
                'extensions' => get_loaded_extensions(),
            ],
            'database' => [
                'version' => $this->getDatabaseVersion(),
            ],
            'os' => [
                'name' => php_uname('s'),
                'release' => php_uname('r'),
                'version' => php_uname('v'),
                'machine' => php_uname('m')
            ],
            'webserver' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'pterodactyl' => [
                'status' => $this->isPterodactylApiOnline(),
            ],
            'pteroca_plugin' => [
                'version' => $this->getPterocaPluginVersion(),
            ],
        ];
    }

    private function getDatabaseVersion(): string
    {
        try {
            return $this->entityManager->getConnection()->getServerVersion();
        } catch (\Exception $exception) {
            return 'N/A';
        }
    }

    private function isPterodactylApiOnline(): bool
    {
        try {
            $this->pterodactylService->getApi();
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function getPterocaPluginVersion(): ?string
    {
        try {
            $data = $this->pterodactylService->getApi()->http->get('pteroca/version');
            
            return $data['version'] ?? null;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
