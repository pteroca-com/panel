<?php

namespace App\Core\DTO\Command\User;

readonly class UnblockUserCommand
{
    public function __construct(
        public string $email,
    ) {}
}
