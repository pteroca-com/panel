<?php

namespace App\Core\Service\Server;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use App\Core\Event\Server\Database\ServerDatabaseCreatedEvent;
use App\Core\Event\Server\Database\ServerDatabaseCreationFailedEvent;
use App\Core\Event\Server\Database\ServerDatabaseCreationRequestedEvent;
use App\Core\Event\Server\Database\ServerDatabaseDeletedEvent;
use App\Core\Event\Server\Database\ServerDatabaseDeletionRequestedEvent;
use App\Core\Event\Server\Database\ServerDatabasePasswordRotatedEvent;
use App\Core\Event\Server\Database\ServerDatabasePasswordRotationRequestedEvent;
use App\Core\Service\Event\EventContextService;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class ServerDatabaseService
{
    public function __construct(
        private PterodactylApplicationService $pterodactylApplicationService,
        private ServerLogService              $serverLogService,
        private EventDispatcherInterface      $eventDispatcher,
        private RequestStack                  $requestStack,
        private EventContextService           $eventContextService,
    ) {}

    public function getAllDatabases(
        Server $server,
        UserInterface $user,
    ): array
    {
        return $this->pterodactylApplicationService
            ->getClientApi($user)
            ->databases()
            ->getDatabases($server->getPterodactylServerIdentifier(), ['include' => 'password'])
            ->toArray();
    }

    /**
     * @throws Exception
     */
    public function createDatabase(
        Server $server,
        UserInterface $user,
        string $databaseName,
        string $connectionsFrom,
    ): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        if (empty($connectionsFrom)) {
            $connectionsFrom = '%';
        }

        $requestedEvent = new ServerDatabaseCreationRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $databaseName,
            $connectionsFrom,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Database creation was blocked';
            throw new RuntimeException($reason);
        }

        $pterodactylClientApi = $this->pterodactylApplicationService
            ->getClientApi($user);

        try {
            $pterodactylClientApi->databases()
                ->createDatabase(
                    $server->getPterodactylServerIdentifier(),
                    $databaseName,
                    $connectionsFrom
                );

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::CREATE_DATABASE,
                [
                    'database' => $databaseName,
                    'remote' => $connectionsFrom,
                ]
            );

            $createdEvent = new ServerDatabaseCreatedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $databaseName,
                $connectionsFrom,
                $context
            );
            $this->eventDispatcher->dispatch($createdEvent);

        } catch (Exception $e) {
            $failedEvent = new ServerDatabaseCreationFailedEvent(
                $user->getId(),
                $server->getId(),
                $server->getPterodactylServerIdentifier(),
                $databaseName,
                $connectionsFrom,
                $e->getMessage(),
                $context
            );
            $this->eventDispatcher->dispatch($failedEvent);
            throw $e;
        }
    }

    public function deleteDatabase(
        Server $server,
        UserInterface $user,
        int $databaseId,
    ): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerDatabaseDeletionRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $databaseId,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Database deletion was blocked';
            throw new RuntimeException($reason);
        }

        $this->pterodactylApplicationService
            ->getClientApi($user)
            ->databases()
            ->deleteDatabase($server->getPterodactylServerIdentifier(), $databaseId);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_DATABASE,
            [
                'database_id' => $databaseId,
            ]
        );

        $deletedEvent = new ServerDatabaseDeletedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $databaseId,
            $context
        );
        $this->eventDispatcher->dispatch($deletedEvent);
    }

    public function rotatePassword(
        Server $server,
        UserInterface $user,
        string $databaseId,
    ): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $requestedEvent = new ServerDatabasePasswordRotationRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $databaseId,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Database password rotation was blocked';
            throw new RuntimeException($reason);
        }

        $rotatedPassword = $this->pterodactylApplicationService
            ->getClientApi($user)
            ->databases()
            ->rotatePassword($server->getPterodactylServerIdentifier(), $databaseId)
            ->toArray();

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::ROTATE_DATABASE_PASSWORD,
            [
                'database_id' => $databaseId,
            ]
        );

        $rotatedEvent = new ServerDatabasePasswordRotatedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $databaseId,
            $context
        );
        $this->eventDispatcher->dispatch($rotatedEvent);

        return $rotatedPassword;
    }
}
