<?php

namespace App\Core\DTO;

use DateTime;

readonly class SystemVersionDTO
{
    public function __construct(
        public string $currentVersion,
        public string $latestVersion,
        public string $zipUrl,
        public string $tarUrl,
        public string $changelog,
        public DateTime $releaseDate,
    ) {}
}
