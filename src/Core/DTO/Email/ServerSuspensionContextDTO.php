<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use DateTimeInterface;

readonly class ServerSuspensionContextDTO 
{
    public function __construct(
        public UserInterface $user,
        public Server $server,
        public string $serverName,
        public DateTimeInterface $suspensionDate,
        public string $siteName,
        public string $siteUrl,
        public string $panelUrl,
        public bool $autoDeleteEnabled,
        public ?int $deleteAfterDays = null,
        public ?DateTimeInterface $deleteDate = null,
    ) {}
}
