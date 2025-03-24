<?php

namespace App\Core\DTO;

readonly class PaymentSessionDTO
{
    public function __construct(
        private string $id,
        private float $amountTotal,
        private string $currency,
        private string $paymentStatus,
        private ?string $url,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getAmountTotal(): float
    {
        return $this->amountTotal;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
