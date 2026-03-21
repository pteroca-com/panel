<?php

namespace App\Core\DTO\Command\User;

readonly class ListUsersCommand
{
    public function __construct(
        public ?string $role = null,
        public ?bool $blocked = null,
        public ?bool $deleted = null,
    ) {}
}
