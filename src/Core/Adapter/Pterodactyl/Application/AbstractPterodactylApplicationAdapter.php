<?php

namespace App\Core\Adapter\Pterodactyl\Application;

use App\Core\Adapter\Pterodactyl\AbstractPterodactylAdapter;
use App\Core\DTO\Pterodactyl\Credentials;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPterodactylApplicationAdapter extends AbstractPterodactylAdapter
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly Credentials $credentials,
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
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
        } catch (Exception $e) {
            throw new Exception(
                sprintf('Failed to make %s request to %s: %s', $method, $endpoint, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    protected function validateServerResponse(ResponseInterface $response, int $expectedStatusCode): array
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new Exception(
                sprintf('Pterodactyl API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        return $response->toArray();
    }
}
