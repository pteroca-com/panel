<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\UserInterface;

class EmailVerificationContextDTO
{
    public function __construct(
        public readonly UserInterface $user,
        public readonly string $verificationUrl,
        public readonly string $siteName,
        public readonly string $siteUrl,
    ) {}
}
