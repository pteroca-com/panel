<?php

namespace App\Core\DTO\Action\Result;

use App\Core\Contract\UserInterface;

class RegisterUserActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?UserInterface $user = null,
        public readonly ?string $error = null
    )
    {
    }
}
