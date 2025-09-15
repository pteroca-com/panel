<?php

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

class PterodactylScheduleTask extends Resource
{
    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getSequenceId(): ?int
    {
        return $this->get('sequence_id');
    }

    public function getAction(): ?string
    {
        return $this->get('action');
    }

    public function getPayload(): ?string
    {
        return $this->get('payload');
    }

    public function getTimeOffset(): ?int
    {
        return $this->get('time_offset');
    }

    public function isContinueOnFailure(): ?bool
    {
        return $this->get('continue_on_failure');
    }

    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }
}
