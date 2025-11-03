<?php

namespace App\Core\Event\Email;

use App\Core\Event\AbstractDomainEvent;
use Symfony\Component\Mime\Email;

/**
 * Event dispatched before an email is sent.
 *
 * Allows plugins to modify email content, template, context, or add recipients.
 * This event is dispatched in MailerService::sendEmail() before the email is actually sent.
 *
 * @example Modifying email template
 * public function onEmailBeforeSend(EmailBeforeSendEvent $event): void
 * {
 *     if ($event->getTemplateName() === 'emails/user_welcome.html.twig') {
 *         $event->setTemplateName('@PluginMyPlugin/emails/custom_welcome.html.twig');
 *     }
 * }
 *
 * @example Adding context data
 * public function onEmailBeforeSend(EmailBeforeSendEvent $event): void
 * {
 *     if ($event->getTemplateName() === 'emails/server_created.html.twig') {
 *         $event->addContext('welcome_bonus', '10 PLN');
 *         $event->addContext('plugin_message', 'Special offer!');
 *     }
 * }
 *
 * @example Replacing entire context
 * public function onEmailBeforeSend(EmailBeforeSendEvent $event): void
 * {
 *     $context = $event->getContext();
 *     $context['custom_footer'] = 'Powered by MyPlugin';
 *     $event->setContext($context);
 * }
 *
 * @example Modifying email subject
 * public function onEmailBeforeSend(EmailBeforeSendEvent $event): void
 * {
 *     $subject = $event->getSubject();
 *     $event->setSubject('[MyPlugin] ' . $subject);
 * }
 *
 * @example Adding BCC for all emails
 * public function onEmailBeforeSend(EmailBeforeSendEvent $event): void
 * {
 *     $event->addBcc('notifications@myplugin.com');
 * }
 *
 * @example Adding CC based on email type
 * public function onEmailBeforeSend(EmailBeforeSendEvent $event): void
 * {
 *     if ($event->getTemplateName() === 'emails/payment_confirmation.html.twig') {
 *         $event->addCc('accounting@company.com');
 *     }
 * }
 */
class EmailBeforeSendEvent extends AbstractDomainEvent
{
    private string $templateName;
    private array $context;
    private string $subject;

    public function __construct(
        private readonly Email $email,
        string $templateName,
        array $context,
        string $subject,
        private readonly string $recipient,
        private readonly array $eventContext = [],
        ?string $eventId = null,
    ) {
        parent::__construct($eventId);
        $this->templateName = $templateName;
        $this->context = $context;
        $this->subject = $subject;
    }

    /**
     * Get the Email object.
     *
     * This is the Symfony Mime\Email object that will be sent.
     * You can modify it directly (add attachments, etc).
     *
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * Get the template name.
     *
     * @return string e.g., 'emails/user_welcome.html.twig'
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Set a different template.
     *
     * Useful for plugins that want to use custom email templates.
     *
     * @param string $templateName Template path (e.g., '@PluginMyPlugin/emails/custom.html.twig')
     * @return void
     */
    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    /**
     * Get template context.
     *
     * Context is the array of variables passed to the Twig template.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set entire template context (replaces existing).
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Add a single context variable.
     *
     * @param string $key Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Set email subject.
     *
     * @param string $subject
     * @return void
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
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
     * Add a CC recipient.
     *
     * @param string $email
     * @return void
     */
    public function addCc(string $email): void
    {
        $this->email->addCc($email);
    }

    /**
     * Add a BCC recipient.
     *
     * @param string $email
     * @return void
     */
    public function addBcc(string $email): void
    {
        $this->email->addBcc($email);
    }

    /**
     * Add a reply-to address.
     *
     * @param string $email
     * @return void
     */
    public function addReplyTo(string $email): void
    {
        $this->email->addReplyTo($email);
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
