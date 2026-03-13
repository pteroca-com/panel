<?php

namespace App\Core\DTO\Command\User;

readonly class RestoreUserCommand
{
    public function __construct(
        public string $email,
    ) {}
}
