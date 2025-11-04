<?php

namespace App\Core\Handler;

use App\Core\Event\Cli\SynchronizeData\DataSyncProcessCompletedEvent;
use App\Core\Event\Cli\SynchronizeData\DataSyncProcessFailedEvent;
use App\Core\Event\Cli\SynchronizeData\DataSyncProcessStartedEvent;
use App\Core\Event\Cli\SynchronizeData\UserPterodactylApiKeyCreatedEvent;
use App\Core\Event\Cli\SynchronizeData\UserPterodactylApiKeyCreationFailedEvent;
use App\Core\Event\Cli\SynchronizeData\UserPterodactylApiKeyCreationRequestedEvent;
use App\Core\Repository\UserRepository;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;
use DateTimeImmutable;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class SynchronizeDataHandler implements HandlerInterface
{
    public function __construct(
        private UserRepository                 $userRepository,
        private PterodactylClientApiKeyService $pterodactylClientApiKeyService,
        private EventDispatcherInterface       $eventDispatcher,
        private EventContextService            $eventContextService,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $startTime = new DateTimeImmutable();
        $context = $this->eventContextService->buildCliContext('app:synchronize-data');

        $this->eventDispatcher->dispatch(
            new DataSyncProcessStartedEvent($startTime, $context)
        );

        $stats = [
            'usersWithoutKeys' => 0,
            'keysCreated' => 0,
            'keysSkipped' => 0,
            'keysFailed' => 0,
        ];

        try {
            $this->synchronizeUserPterodactylKeys($context, $stats);

            $endTime = new DateTimeImmutable();
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();

            $this->eventDispatcher->dispatch(
                new DataSyncProcessCompletedEvent(
                    $stats['usersWithoutKeys'],
                    $stats['keysCreated'],
                    $stats['keysSkipped'],
                    $stats['keysFailed'],
                    $duration,
                    $endTime,
                    $context
                )
            );
        } catch (Exception $e) {
            $this->eventDispatcher->dispatch(
                new DataSyncProcessFailedEvent(
                    $e->getMessage(),
                    $stats,
                    new DateTimeImmutable(),
                    $context
                )
            );

            throw $e;
        }
    }

    private function synchronizeUserPterodactylKeys(array $context, array &$stats): void
    {
        $usersWithoutPterodactylKeys = $this->userRepository
            ->findBy(['pterodactylUserApiKey' => null]);

        $stats['usersWithoutKeys'] = count($usersWithoutPterodactylKeys);

        foreach ($usersWithoutPterodactylKeys as $user) {
            $userId = $user->getId() ?? 0;
            $userEmail = $user->getEmail() ?? '';
            $userName = $user->getUserIdentifier();

            $requestedEvent = new UserPterodactylApiKeyCreationRequestedEvent(
                $userId,
                $userEmail,
                $userName,
                $context
            );
            $this->eventDispatcher->dispatch($requestedEvent);

            if ($requestedEvent->isPropagationStopped()) {
                $stats['keysSkipped']++;
                continue;
            }

            try {
                $pterodactylClientApiKey = $this->pterodactylClientApiKeyService->createClientApiKey($user);
                $user->setPterodactylUserApiKey($pterodactylClientApiKey);
                $this->userRepository->save($user);

                $stats['keysCreated']++;

                $this->eventDispatcher->dispatch(
                    new UserPterodactylApiKeyCreatedEvent(
                        $userId,
                        $userEmail,
                        $userName,
                        '***', // API key masked for security
                        new DateTimeImmutable(),
                        $context
                    )
                );
            } catch (Exception $e) {
                $stats['keysFailed']++;

                $this->eventDispatcher->dispatch(
                    new UserPterodactylApiKeyCreationFailedEvent(
                        $userId,
                        $userEmail,
                        $userName,
                        $e->getMessage(),
                        $context
                    )
                );
                // Continue processing other users
            }
        }
    }
}
