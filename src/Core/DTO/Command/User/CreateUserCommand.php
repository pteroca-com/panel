<?php

namespace App\Core\DTO\Command\User;

readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $roleName,
        public bool $allowCreateWithoutApiKey = false,
    ) {}
}
