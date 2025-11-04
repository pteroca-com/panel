<?php

declare(strict_types=1);

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylFilesInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylFile;
use App\Core\DTO\Pterodactyl\Collection;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class PterodactylFiles extends AbstractPterodactylClientAdapter implements PterodactylFilesInterface
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function listFiles(string $serverId, string $directory = '/'): Collection
    {
        $response = $this->makeRequest('GET', "servers/$serverId/files/list", [
            'query' => ['directory' => $directory],
        ]);
        $data = $this->validateListResponse($response, 200);

        $items = array_map(
            fn(array $file): PterodactylFile => new PterodactylFile($file),
            $data['data'] ?? []
        );

        return new Collection($items, $this->getMetaFromResponse($data));
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    public function readFileContents(string $serverId, string $filePath): string
    {
        $response = $this->makeRequest('GET', "servers/$serverId/files/contents", [
            'query' => ['file' => $filePath],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function writeFile(string $serverId, string $filePath, string $content): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/write", [
            'query' => ['file' => $filePath],
            'body' => $content,
            'headers' => ['Content-Type' => 'text/plain'],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function deleteFiles(string $serverId, string $root, array $files): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/delete", [
            'json' => [
                'root' => $root,
                'files' => $files,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function createDirectory(string $serverId, string $root, string $name): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/create-folder", [
            'json' => [
                'root' => $root,
                'name' => $name,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function renameFiles(string $serverId, string $root, array $files): void
    {
        $response = $this->makeRequest('PUT', "servers/$serverId/files/rename", [
            'json' => [
                'root' => $root,
                'files' => $files,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function copyFile(string $serverId, string $location): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/copy", [
            'json' => [
                'location' => $location,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function compressFiles(string $serverId, string $root, array $files): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/compress", [
            'json' => [
                'root' => $root,
                'files' => $files,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function decompressFile(string $serverId, string $root, string $file): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/decompress", [
            'json' => [
                'root' => $root,
                'file' => $file,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function changePermissions(string $serverId, string $root, array $files): void
    {
        $response = $this->makeRequest('POST', "servers/$serverId/files/chmod", [
            'json' => [
                'root' => $root,
                'files' => $files,
            ],
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }
    }
}
