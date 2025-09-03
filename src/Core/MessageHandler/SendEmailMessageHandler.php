<?php

namespace App\Core\MessageHandler;

use App\Core\Message\SendEmailMessage;
use App\Core\Service\Mailer\MailerServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendEmailMessageHandler
{

    public function __construct(
        private MailerServiceInterface $mailerService,
    ) {}

    public function __invoke(SendEmailMessage $message): void
    {
        $this->mailerService->sendEmail(
            $message->getTo(),
            $message->getSubject(),
            $message->getTemplate(),
            $message->getContext()
        );
    }
}
