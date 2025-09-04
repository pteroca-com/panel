<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\UserInterface;

readonly class EmailContextDTO
{
    public function __construct(
        private UserInterface $user,
        private string $currency,
        private array $serverData,
        private array $panelData,
    ) {}

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getServerData(): array
    {
        return $this->serverData;
    }

    public function getPanelData(): array
    {
        return $this->panelData;
    }

    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'currency' => $this->currency,
            'server' => $this->serverData,
            'panel' => $this->panelData,
        ];
    }
}
