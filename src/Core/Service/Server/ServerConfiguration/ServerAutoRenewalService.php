<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Entity\Server;
use App\Core\Event\Server\Configuration\ServerAutoRenewalToggleRequestedEvent;
use App\Core\Event\Server\Configuration\ServerAutoRenewalToggledEvent;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Event\EventContextService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ServerAutoRenewalService
{
    public function __construct(
        private readonly ServerRepository $serverRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
    )
    {
    }

    public function toggleAutoRenewal(Server $server, bool $toggle, int $userId, array $context = []): void
    {
        if (empty($context)) {
            $request = $this->requestStack->getCurrentRequest();
            $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];
        }

        $currentValue = $server->isAutoRenewal();

        $requestedEvent = new ServerAutoRenewalToggleRequestedEvent(
            $userId,
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $toggle,
            $currentValue,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Server auto-renewal toggle was blocked';
            throw new \Exception($reason);
        }

        $server->setAutoRenewal($toggle);
        $this->serverRepository->save($server);

        $toggledEvent = new ServerAutoRenewalToggledEvent(
            $userId,
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $toggle,
            $currentValue,
            $context
        );
        $this->eventDispatcher->dispatch($toggledEvent);
    }
}
