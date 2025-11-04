<?php

namespace App\Core\DTO\Email;

use App\Core\Contract\UserInterface;

readonly class EmailVerificationContextDTO
{
    public function __construct(
        public UserInterface $user,
        public string        $verificationUrl,
        public string        $siteName,
        public string        $siteUrl,
    ) {}
}
