<?php

namespace App\Core\Event\Payment;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Payment;
use App\Core\Event\AbstractDomainEvent;
use App\Core\Event\StoppableEventTrait;

class PaymentContinueRequestedEvent extends AbstractDomainEvent
{
    use StoppableEventTrait;

    public function __construct(
        private readonly Payment $payment,
        private readonly ?UserInterface $user = null,
        private readonly array $context = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getSessionId(): string
    {
        return $this->payment->getSessionId();
    }
}
