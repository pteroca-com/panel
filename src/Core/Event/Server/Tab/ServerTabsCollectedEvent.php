<?php

namespace App\Core\Event\Server\Tab;

use App\Core\DTO\ServerTabContext;
use App\Core\Event\AbstractDomainEvent;
use App\Core\Service\Tab\ServerTabRegistry;

class ServerTabsCollectedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly ServerTabRegistry $registry,
        private readonly ServerTabContext $tabContext,
        private readonly array $context = [],
        ?string $eventId = null
    ) {
        parent::__construct($eventId);
    }

    public function getRegistry(): ServerTabRegistry
    {
        return $this->registry;
    }

    public function getTabContext(): ServerTabContext
    {
        return $this->tabContext;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
