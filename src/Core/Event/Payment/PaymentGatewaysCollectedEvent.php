<?php

namespace App\Core\Event\Payment;

use App\Core\Event\AbstractDomainEvent;
use App\Core\Service\Payment\PaymentGatewayManager;

/**
 * Event dispatched when collecting payment gateways.
 *
 * Allows plugins to register custom payment providers.
 *
 * @example Event Subscriber
 * public function onGatewaysCollected(PaymentGatewaysCollectedEvent $event): void
 * {
 *     $event->getGatewayManager()->registerProvider(new MyCustomProvider());
 * }
 */
class PaymentGatewaysCollectedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    /**
     * Get the gateway manager to add/modify providers.
     *
     * @return PaymentGatewayManager
     */
    public function getGatewayManager(): PaymentGatewayManager
    {
        return $this->gatewayManager;
    }

    /**
     * Get event context (IP, user agent, locale).
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
