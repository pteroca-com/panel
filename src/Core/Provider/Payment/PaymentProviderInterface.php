<?php

namespace App\Core\Provider\Payment;

use App\Core\DTO\PaymentSessionDTO;

interface PaymentProviderInterface
{
    public const PAID_STATUS = 'paid';

    public function createSession(
        float $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
    ): ?PaymentSessionDTO;

    public function retrieveSession(string $sessionId): ?PaymentSessionDTO;

    public function getIdentifier(): string;

    public function getDisplayName(): string;

    public function getIcon(): string;

    public function isConfigured(): bool;

    public function getDescription(): string;

    public function getSupportedCurrencies(): array;

    public function getMetadata(): array;
}