<?php

namespace App\Core\DTO\Action\Result;

class RedeemVoucherActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $type,
        public readonly ?float $value,
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'type' => $this->type,
            'value' => $this->value,
        ];
    }

    public static function success(string $message, ?string $type, ?string $value): self
    {
        return new self(true, $message, $type, $value);
    }

    public static function failure(string $message, ?string $type = null, ?string $value = null): self
    {
        return new self(false, $message, $type, $value);
    }
}
