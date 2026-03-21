<?php

namespace App\Core\DTO\Command\User;

readonly class DeleteUserCommand
{
    public function __construct(
        public string $email,
    ) {}
}
