<?php

namespace App\Core\DTO\Command\User;

readonly class ChangeUserBalanceCommand
{
    public function __construct(
        public string $email,
        public float $amount,
        public string $mode,
    ) {}
}
