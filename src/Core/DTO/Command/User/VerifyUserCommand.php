<?php

namespace App\Core\DTO\Command\User;

readonly class VerifyUserCommand
{
    public function __construct(
        public string $email,
    ) {}
}
