<?php

declare(strict_types=1);

namespace App\Core\DTO\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Resource;

/**
 * @property bool $isFile
 * @property int $size
 */
final class PterodactylFile extends Resource
{
    public function isDirectory(): bool
    {
        return !$this->isFile;
    }

    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
