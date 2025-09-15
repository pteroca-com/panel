<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylAccountInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylBackupsInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylClientAdapterInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylDatabasesInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylFilesInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylNetworkInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylSchedulesInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylServersInterface;
use App\Core\Contract\Pterodactyl\Client\PterodactylUsersInterface;
use App\Core\DTO\Pterodactyl\Credentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PterodactylClientAdapter implements PterodactylClientAdapterInterface
{
    private ?PterodactylAccountInterface $accountAdapter = null;
    private ?PterodactylServersInterface $serversAdapter = null;
    private ?PterodactylBackupsInterface $backupsAdapter = null;
    private ?PterodactylSchedulesInterface $schedulesAdapter = null;
    private ?PterodactylNetworkInterface $networkAdapter = null;
    private ?PterodactylDatabasesInterface $databasesAdapter = null;
    private ?PterodactylFilesInterface $filesAdapter = null;
    private ?PterodactylUsersInterface $usersAdapter = null;
    private ?Credentials $credentials = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function setCredentials(Credentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    public function account(): PterodactylAccountInterface
    {
        if ($this->accountAdapter === null) {
            $this->accountAdapter = new PterodactylAccount($this->httpClient, $this->credentials);
        }

        return $this->accountAdapter;
    }

    public function servers(): PterodactylServersInterface
    {
        if ($this->serversAdapter === null) {
            $this->serversAdapter = new PterodactylServers($this->httpClient, $this->credentials);
        }

        return $this->serversAdapter;
    }

    public function backups(): PterodactylBackupsInterface
    {
        if ($this->backupsAdapter === null) {
            $this->backupsAdapter = new PterodactylBackups($this->httpClient, $this->credentials);
        }

        return $this->backupsAdapter;
    }

    public function schedules(): PterodactylSchedulesInterface
    {
        if ($this->schedulesAdapter === null) {
            $this->schedulesAdapter = new PterodactylSchedules($this->httpClient, $this->credentials);
        }

        return $this->schedulesAdapter;
    }

    public function network(): PterodactylNetworkInterface
    {
        if ($this->networkAdapter === null) {
            $this->networkAdapter = new PterodactylNetwork($this->httpClient, $this->credentials);
        }

        return $this->networkAdapter;
    }

    public function databases(): PterodactylDatabasesInterface
    {
        if ($this->databasesAdapter === null) {
            $this->databasesAdapter = new PterodactylDatabases($this->httpClient, $this->credentials);
        }

        return $this->databasesAdapter;
    }

    public function files(): PterodactylFilesInterface
    {
        if ($this->filesAdapter === null) {
            $this->filesAdapter = new PterodactylFiles($this->httpClient, $this->credentials);
        }

        return $this->filesAdapter;
    }

    public function users(): PterodactylUsersInterface
    {
        if ($this->usersAdapter === null) {
            $this->usersAdapter = new PterodactylUsers($this->httpClient, $this->credentials);
        }

        return $this->usersAdapter;
    }
}
