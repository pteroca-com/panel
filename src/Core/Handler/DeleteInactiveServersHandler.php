<?php

namespace App\Core\Handler;

use App\Core\Enum\SettingEnum;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\SettingRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use DateTime;

readonly class DeleteInactiveServersHandler implements HandlerInterface
{
    private const DEFAULT_DELETE_INACTIVE_SERVERS_DAYS_AFTER = 30;

    public function __construct(
        private ServerRepository $serverRepository,
        private PterodactylService $pterodactylService,
        private SettingRepository $settingRepository,
    ) {}

    public function handle(): void
    {
       $this->handleDeleteInactiveServers();
    }

    private function handleDeleteInactiveServers(): void
    {
        $dateObject = new DateTime(sprintf('now - %d days', $this->getDeleteInactiveServersDaysAfter()));
        $serversToDelete = $this->serverRepository->getServersExpiredBefore($dateObject);
        foreach ($serversToDelete as $server) {
            $this->pterodactylService->getApi()->servers->delete($server->getPterodactylServerId());
            $this->serverRepository->delete($server);
        }
    }

    private function getDeleteInactiveServersDaysAfter(): int
    {
        $settingValue = $this->settingRepository
            ->getSetting(SettingEnum::DELETE_SUSPENDED_SERVERS_DAYS_AFTER);

        if (empty($settingValue) || !is_numeric($settingValue)) {
            return self::DEFAULT_DELETE_INACTIVE_SERVERS_DAYS_AFTER;
        }

        return (int) $settingValue;
    }
}
