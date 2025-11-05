<?php

namespace App\Core\Service\System;

use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class SystemInformationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PterodactylApplicationService $pterodactylApplicationService,
    )
    {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDatabaseVersion(): string
    {
        try {
            return $this->entityManager
                ->getConnection()
                ->getServerVersion();
        } catch (Exception) {
            return 'N/A';
        }
    }

    private function isPterodactylApiOnline(): bool
    {
        try {
            $this->pterodactylApplicationService
                ->getApplicationApi()
                ->locations()
                ->all();
            return true;
        } catch (Exception) {
            return false;
        }
    }

    private function getPterocaPluginVersion(): ?string
    {
        try {
            $data = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->pteroca()
                ->getVersion();
            
            return $data['version'] ?? null;
        } catch (Exception) {
            return null;
        }
    }
}
