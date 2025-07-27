<?php

namespace App\Core\DTO;

use App\Core\Enum\ServerStateEnum;

readonly class ServerDetailsDTO
{
    public function __construct(
        public string $identifier,
        public string $name,
        public string $description,
        public string $ip,
        public array $limits,
        public array $featureLimits,
        public array $egg,
        public ?ServerStateEnum $state = null,
    ) {}

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'description' => $this->description,
            'ip' => $this->ip,
            'limits' => $this->limits,
            'featureLimits' => $this->featureLimits,
            'egg' => $this->egg,
            'state' => $this->state?->value,
        ];
    }
}
