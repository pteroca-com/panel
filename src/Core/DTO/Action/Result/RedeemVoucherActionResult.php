<?php

namespace App\Core\DTO\Action\Result;

class RedeemVoucherActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public static function success(string $message): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
