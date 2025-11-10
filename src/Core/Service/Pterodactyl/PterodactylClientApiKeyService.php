<?php

namespace App\Core\Service\Pterodactyl;

use App\Core\Contract\UserInterface;
use App\Core\Exception\CouldNotCreatePterodactylClientApiKeyException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PterodactylClientApiKeyService
{
    private const PTERODACTYL_CLIENT_API_KEY_DESCRIPTION = 'PteroCA Client API Key';

    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param UserInterface $user
     * @return string
     * @throws ClientExceptionInterface
     * @throws CouldNotCreatePterodactylClientApiKeyException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     */
    public function createClientApiKey(UserInterface $user): string
    {
        try {
            $apiKey = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->users()
                ->createApiKeyForUser(
                    $user->getPterodactylUserId(),
                    self::PTERODACTYL_CLIENT_API_KEY_DESCRIPTION
                );

            return $apiKey->getFullApiKey();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotCreatePterodactylClientApiKeyException();
        }
    }
}
