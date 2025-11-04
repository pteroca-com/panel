<?php

namespace App\Core\DTO\Action\Result;

use App\Core\Contract\UserInterface;

readonly class RegisterUserActionResult
{
    public function __construct(
        public bool           $success,
        public ?UserInterface $user = null,
        public ?string        $error = null
    )
    {
    }
}
