<?php

namespace App\Core\Adapter\Pterodactyl;

use App\Core\DTO\Pterodactyl\Credentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPterodactylAdapter
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly Credentials $credentials,
    ) {}

    protected function makeRequest(string $method, string $endpoint, array $additionalOptions = []): ResponseInterface
    {
        $url = sprintf('%s/api/application/%s', $this->credentials->getUrl(), $endpoint);

        $options = array_merge([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->credentials->getApiKey(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
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

    protected function validateServerResponse(ResponseInterface $response, int $expectedStatusCode): array
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new \Exception(
                sprintf('Pterodactyl API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();

        if (!isset($data['attributes'])) {
            throw new \Exception('Invalid response format from Pterodactyl API');
        }

        return $data;
    }
}
