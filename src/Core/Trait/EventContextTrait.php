<?php

namespace App\Core\Trait;

use App\Core\Enum\ViewNameEnum;
use App\Core\Event\View\ViewDataEvent;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use App\Core\Service\Event\EventContextService;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

trait EventContextTrait
{
    private ?EventContextService $eventContextService = null;
    private ?EventDispatcherInterface $eventDispatcher = null;

    #[Required]
    public function setEventContextService(EventContextService $eventContextService): void
    {
        $this->eventContextService = $eventContextService;
    }

    #[Required]
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

    protected function dispatchSimpleEvent(
        string $eventClass,
        Request $request,
    ): object {
        $context = $this->buildMinimalEventContext($request);

        return $this->dispatchEvent(
            new $eventClass(
                $this->getUser()?->getId(),
                $context
            )
        );
    }

    protected function dispatchDataEvent(
        string $eventClass,
        Request $request,
        array $eventData
    ): object {
        $context = $this->buildMinimalEventContext($request);

        return $this->dispatchEvent(
            new $eventClass(
                $this->getUser()?->getId(),
                ...array_merge($eventData, [$context])
            )
        );
    }

    protected function dispatchContextEvent(
        string $eventClass,
        Request $request
    ): object {
        $context = $this->buildMinimalEventContext($request);

        return $this->dispatchEvent(
            new $eventClass($context)
        );
    }

    protected function prepareViewDataEvent(
        ViewNameEnum $viewName,
        array $viewData,
        Request $request
    ): ViewDataEvent
    {
        $context = $this->buildMinimalEventContext($request);

        $viewEvent = new ViewDataEvent(
            $viewName->value,
            $viewData,
            $this->getUser(),
            $context
        );

        return $this->dispatchEvent($viewEvent);
    }

    private function getEventContextService(): EventContextService
    {
        if ($this->eventContextService === null) {
            throw new LogicException(
                'EventContextService not injected. Make sure the controller is registered as a service with autowiring enabled.'
            );
        }

        return $this->eventContextService;
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            throw new LogicException(
                'EventDispatcher not injected. Make sure the controller is registered as a service with autowiring enabled.'
            );
        }

        return $this->eventDispatcher;
    }
}
