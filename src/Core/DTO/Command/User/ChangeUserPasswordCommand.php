<?php

namespace App\Core\DTO\Command\User;

readonly class ChangeUserPasswordCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
