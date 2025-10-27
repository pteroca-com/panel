<?php

namespace App\Core\Event\Dashboard;

use App\Core\Contract\UserInterface;
use App\Core\Event\AbstractDomainEvent;
use App\Core\Service\Widget\DashboardWidgetRegistry;

class DashboardWidgetsCollectedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly DashboardWidgetRegistry $registry,
        private readonly UserInterface $user,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getRegistry(): DashboardWidgetRegistry
    {
        return $this->registry;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
