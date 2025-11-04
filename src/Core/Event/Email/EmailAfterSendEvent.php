<?php

namespace App\Core\Event\Email;

use App\Core\Event\AbstractDomainEvent;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Event dispatched after an email has been sent (or failed to send).
 *
 * This event is dispatched in MailerService::sendEmail() after the email sending attempt.
 * Useful for logging, statistics, or triggering additional actions based on email delivery.
 *
 * @example Logging successful email delivery
 * public function onEmailAfterSend(EmailAfterSendEvent $event): void
 * {
 *     if ($event->wasSuccessful()) {
 *         $this->logger->info('Email sent successfully', [
 *             'template' => $event->getTemplateName(),
 *             'recipient' => $event->getRecipient(),
 *         ]);
 *     }
 * }
 *
 * @example Handling email failures
 * public function onEmailAfterSend(EmailAfterSendEvent $event): void
 * {
 *     if (!$event->wasSuccessful()) {
 *         $this->logger->error('Email failed to send', [
 *             'template' => $event->getTemplateName(),
 *             'recipient' => $event->getRecipient(),
 *             'error' => $event->getException()?->getMessage(),
 *         ]);
 *
 *         // Trigger fallback notification mechanism
 *         $this->sendFallbackNotification($event->getRecipient());
 *     }
 * }
 *
 * @example Tracking email statistics
 * public function onEmailAfterSend(EmailAfterSendEvent $event): void
 * {
 *     if ($event->wasSuccessful()) {
 *         $this->stats->incrementEmailSent($event->getTemplateName());
 *     } else {
 *         $this->stats->incrementEmailFailed($event->getTemplateName());
 *     }
 * }
 *
 * @example Sending webhook notification
 * public function onEmailAfterSend(EmailAfterSendEvent $event): void
 * {
 *     if ($event->getTemplateName() === 'emails/payment_confirmation.html.twig') {
 *         $this->webhookService->notify([
 *             'event' => 'email.sent',
 *             'template' => $event->getTemplateName(),
 *             'recipient' => $event->getRecipient(),
 *             'success' => $event->wasSuccessful(),
 *         ]);
 *     }
 * }
 */
class EmailAfterSendEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly Email      $email,
        private readonly string     $templateName,
        private readonly string     $recipient,
        private readonly bool       $success,
        private readonly ?Throwable $exception = null,
        private readonly array      $eventContext = [],
        ?string                     $eventId = null,
    ) {
        parent::__construct($eventId);
    }

    /**
     * Get the Email object that was sent.
     *
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * Get the template name that was used.
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Get recipient email address.
     *
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * Check if email was sent successfully.
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the exception if email sending failed.
     *
     * Returns null if email was sent successfully.
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Get event context (request metadata).
     *
     * Contains IP, user agent, locale, etc.
     *
     * @return array
     */
    public function getEventContext(): array
    {
        return $this->eventContext;
    }
}
