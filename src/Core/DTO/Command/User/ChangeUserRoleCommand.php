<?php

namespace App\Core\DTO\Command\User;

readonly class ChangeUserRoleCommand
{
    public function __construct(
        public string $email,
        public string $role,
    ) {}
}
