<?php

namespace App\Core\DTO;

readonly class ServerDetailsDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public string $ip,
        public array $limits,
        public array $featureLimits,
        public array $egg,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'ip' => $this->ip,
            'limits' => $this->limits,
            'featureLimits' => $this->featureLimits,
            'egg' => $this->egg,
        ];
    }
}
