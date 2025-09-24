<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\UserInterface;

class RegistrationEmailContextDTO
{
    public function __construct(
        public readonly UserInterface $user,
        public readonly string $siteName,
        public readonly string $siteUrl,
        public readonly ?string $verificationUrl = null,
    ) {}
}
