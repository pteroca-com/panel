<?php

namespace App\Core\Message;

readonly class SendEmailMessage
{

    public function __construct(
        private string $to,
        private string $subject,
        private string $template,
        private array $context
    ) {}

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
