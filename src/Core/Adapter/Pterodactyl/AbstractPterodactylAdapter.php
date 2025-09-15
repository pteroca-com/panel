<?php

namespace App\Core\Adapter\Pterodactyl;

abstract class AbstractPterodactylAdapter
{
    protected function getDataFromResponse(array $response): array
    {
        if (isset($response['data'])) {
            return $response['data'];
        }

        return $response['attributes'] ?? [];
    }

    protected function getMetaFromResponse(array $response): array
    {
        return $response['meta'] ?? [];
    }
}
