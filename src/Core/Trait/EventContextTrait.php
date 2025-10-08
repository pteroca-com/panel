<?php

namespace App\Core\Trait;

use App\Core\Service\Event\EventContextService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

trait EventContextTrait
{
    private ?EventContextService $eventContextService = null;
    private ?EventDispatcherInterface $eventDispatcher = null;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setEventContextService(EventContextService $eventContextService): void
    {
        $this->eventContextService = $eventContextService;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function buildEventContext(Request $request, array $additionalContext = []): array
    {
        return $this->getEventContextService()->buildContext($request, $additionalContext);
    }

    protected function buildMinimalEventContext(Request $request, array $additionalContext = []): array
    {
        return $this->getEventContextService()->buildMinimalContext($request, $additionalContext);
    }

    protected function buildNullableEventContext(?Request $request, array $additionalContext = []): array
    {
        return $this->getEventContextService()->buildNullableContext($request, $additionalContext);
    }

    protected function dispatchEvent(object $event): object
    {
        return $this->getEventDispatcher()->dispatch($event);
    }

    private function getEventContextService(): EventContextService
    {
        if ($this->eventContextService === null) {
            throw new \LogicException(
                'EventContextService not injected. Make sure the controller is registered as a service with autowiring enabled.'
            );
        }

        return $this->eventContextService;
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            throw new \LogicException(
                'EventDispatcher not injected. Make sure the controller is registered as a service with autowiring enabled.'
            );
        }

        return $this->eventDispatcher;
    }
}
