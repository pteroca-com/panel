<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Adapter\Pterodactyl\AbstractPterodactylAdapter;
use App\Core\DTO\Pterodactyl\Credentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPterodactylClientAdapter extends AbstractPterodactylAdapter
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly Credentials $credentials,
    ) {}

    protected function makeRequest(string $method, string $endpoint, array $additionalOptions = []): ResponseInterface
    {
        $url = sprintf('%s/api/client/%s', $this->credentials->getUrl(), $endpoint);

        $options = array_merge([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->credentials->getApiKey(),
                'Content-Type' => 'application/json',
                'Accept' => 'Application/vnd.pterodactyl.v1+json',
            ],
        ], $additionalOptions);

        try {
            return $this->httpClient->request($method, $url, $options);
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf('Failed to make %s request to %s: %s', $method, $endpoint, $e->getMessage()),
                0,
                $e
            );
        }
    }

    protected function validateClientResponse(ResponseInterface $response, int $expectedStatusCode): array
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new \Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();

        if (!isset($data['attributes'])) {
            throw new \Exception('Invalid response format from Pterodactyl Client API');
        }

        return $data;
    }

    protected function validateListResponse(ResponseInterface $response, int $expectedStatusCode): array
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new \Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();

        if (!isset($data['data'])) {
            throw new \Exception('Invalid list response format from Pterodactyl Client API');
        }

        return $data;
    }
}
