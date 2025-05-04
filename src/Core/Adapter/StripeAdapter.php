<?php

namespace App\Core\Adapter;

use App\Core\DTO\PaymentSessionDTO;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeAdapter
{
    public function setApiKey(string $apiKey): void
    {
        Stripe::setApiKey($apiKey);
    }


    /**
     * @throws ApiErrorException
     */
    public function createSession(array $params): PaymentSessionDTO
    {
        return $this->getPaymentSessionDTO(Session::create($params));
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveSession(string $sessionId): PaymentSessionDTO
    {
        return $this->getPaymentSessionDTO(Session::retrieve($sessionId));
    }

    private function getPaymentSessionDTO(Session $session): PaymentSessionDTO
    {
        return new PaymentSessionDTO(
            $session->id,
            $session->amount_total / 100,
            $session->currency,
            $session->payment_status,
            $session->url
        );
    }
}