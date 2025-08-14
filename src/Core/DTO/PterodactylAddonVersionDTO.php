<?php

namespace App\Core\DTO;

use DateTime;

readonly class PterodactylAddonVersionDTO
{
    public function __construct(
        public string $latestVersion,
        public string $zipUrl,
        public string $tarUrl,
        public string $changelog,
        public DateTime $releaseDate,
    ) {}
}
