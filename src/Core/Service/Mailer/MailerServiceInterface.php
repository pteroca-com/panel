<?php

namespace App\Core\Service\Mailer;

interface MailerServiceInterface
{
    public function sendEmail(string $to, string $subject, string $template, array $context): void;
}