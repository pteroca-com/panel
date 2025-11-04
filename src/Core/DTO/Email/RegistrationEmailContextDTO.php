<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\UserInterface;

readonly class RegistrationEmailContextDTO
{
    public function __construct(
        public UserInterface $user,
        public string        $siteName,
        public string        $siteUrl,
        public ?string       $verificationUrl = null,
    ) {}
}
