<?php

namespace App\Core\DTO\Command\User;

readonly class BlockUserCommand
{
    public function __construct(
        public string $email,
    ) {}
}
