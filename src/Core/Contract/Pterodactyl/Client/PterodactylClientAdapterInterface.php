<?php

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Credentials;

interface PterodactylClientAdapterInterface
{
    public function setCredentials(Credentials $credentials): void;

    public function account(): PterodactylAccountInterface;

    public function servers(): PterodactylServersInterface;

    public function backups(): PterodactylBackupsInterface;

    public function schedules(): PterodactylSchedulesInterface;

    public function network(): PterodactylNetworkInterface;

    public function databases(): PterodactylDatabasesInterface;

    public function files(): PterodactylFilesInterface;

    public function users(): PterodactylUsersInterface;
}
