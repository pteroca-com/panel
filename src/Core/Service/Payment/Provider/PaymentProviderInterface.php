<?php

namespace App\Core\Service\Payment\Provider;

interface PaymentProviderInterface
{
    public function createSession(
        float $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
    ): mixed;

    public function retrieveSession(string $sessionId): mixed;
}