<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylSchedule extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getCron(): ?array
    {
        return $this->get('cron');
    }

    public function getCronMinute(): ?string
    {
        $cron = $this->getCron();
        return $cron['minute'] ?? null;
    }

    public function getCronHour(): ?string
    {
        $cron = $this->getCron();
        return $cron['hour'] ?? null;
    }

    public function getCronDayOfMonth(): ?string
    {
        $cron = $this->getCron();
        return $cron['day_of_month'] ?? null;
    }

    public function getCronMonth(): ?string
    {
        $cron = $this->getCron();
        return $cron['month'] ?? null;
    }

    public function getCronDayOfWeek(): ?string
    {
        $cron = $this->getCron();
        return $cron['day_of_week'] ?? null;
    }

    public function isActive(): ?bool
    {
        return $this->get('is_active');
    }

    public function isProcessing(): ?bool
    {
        return $this->get('is_processing');
    }

    public function isOnlyWhenOnline(): ?bool
    {
        return $this->get('only_when_online');
    }

    public function getLastRunAt(): ?string
    {
        return $this->get('last_run_at');
    }

    public function getNextRunAt(): ?string
    {
        return $this->get('next_run_at');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }

    public function getTasks(): ?array
    {
        $relationships = $this->get('relationships');
        return $relationships['tasks']['data'] ?? null;
    }
}
