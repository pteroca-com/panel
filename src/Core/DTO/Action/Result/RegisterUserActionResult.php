<?php

namespace App\Core\DTO\Action\Result;

use App\Core\Entity\User;

class RegisterUserActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?string $error = null
    )
    {
    }
}
