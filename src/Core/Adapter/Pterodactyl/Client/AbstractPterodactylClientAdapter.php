<?php

namespace App\Core\Adapter\Pterodactyl\Client;

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

abstract class AbstractPterodactylClientAdapter extends AbstractPterodactylAdapter
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
    protected function validateClientResponse(ResponseInterface $response, int $expectedStatusCode): array
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();

        if (!isset($data['attributes'])) {
            throw new Exception('Invalid response format from Pterodactyl Client API');
        }

        return $data;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    protected function validateListResponse(ResponseInterface $response, int $expectedStatusCode): array
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new Exception(
                sprintf('Pterodactyl Client API error: %d %s',
                    $response->getStatusCode(),
                    $response->getContent(false)
                )
            );
        }

        $data = $response->toArray();

        if (!isset($data['data'])) {
            throw new Exception('Invalid list response format from Pterodactyl Client API');
        }

        return $data;
    }
}
