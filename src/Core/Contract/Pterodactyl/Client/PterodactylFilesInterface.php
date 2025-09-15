<?php

declare(strict_types=1);

namespace App\Core\Contract\Pterodactyl\Client;

use App\Core\DTO\Pterodactyl\Client\PterodactylFile;
use App\Core\DTO\Pterodactyl\Collection;

interface PterodactylFilesInterface
{
    /**
     * @return Collection<PterodactylFile>
     */
    public function listFiles(string $serverId, string $directory = '/'): Collection;

    public function readFileContents(string $serverId, string $filePath): string;

    public function writeFile(string $serverId, string $filePath, string $content): void;

    public function deleteFiles(string $serverId, string $root, array $files): void;

    public function createDirectory(string $serverId, string $root, string $name): void;

    public function renameFiles(string $serverId, string $root, array $files): void;

    public function copyFile(string $serverId, string $location): void;

    public function compressFiles(string $serverId, string $root, array $files): void;

    public function decompressFile(string $serverId, string $root, string $file): void;

    public function changePermissions(string $serverId, string $root, array $files): void;
}
