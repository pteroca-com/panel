<?php

namespace App\Core\DTO\Command\User;

readonly class GetUserInfoCommand
{
    public function __construct(
        public string $identifier,
    ) {}
}
