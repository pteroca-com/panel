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
}
