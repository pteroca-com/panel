<?php

namespace App\Core\EventSubscriber\Authentication;

use App\Core\Event\User\Authentication\UserAuthenticationFailedEvent;
use App\Core\Event\User\Authentication\UserAuthenticationSuccessfulEvent;
use App\Core\Event\User\Authentication\UserLoggedInEvent;
use App\Core\Event\User\Authentication\UserLoggedOutEvent;
use App\Core\Event\User\Authentication\UserLoginAttemptedEvent;
use App\Core\Event\User\Authentication\UserLoginRequestedEvent;
use App\Core\Event\User\Authentication\UserLoginValidatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserAuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserLoginRequestedEvent::class => 'onLoginRequested',
            UserLoginAttemptedEvent::class => 'onLoginAttempted',
            UserLoginValidatedEvent::class => 'onLoginValidated',
            UserAuthenticationSuccessfulEvent::class => 'onAuthenticationSuccessful',
            UserLoggedInEvent::class => 'onLoggedIn',
            UserAuthenticationFailedEvent::class => 'onAuthenticationFailed',
            UserLoggedOutEvent::class => 'onLoggedOut',
        ];
    }

    public function onLoginRequested(UserLoginRequestedEvent $event): void
    {
        $this->logger->info('Login form requested', [
            'eventId' => $event->getEventId(),
            'ip' => $event->getIp(),
            'userAgent' => $event->getUserAgent(),
            'locale' => $event->getLocale(),
            'referer' => $event->getReferer(),
        ]);
    }

    public function onLoginAttempted(UserLoginAttemptedEvent $event): void
    {
        $this->logger->info('Login attempted', [
            'eventId' => $event->getEventId(),
            'email' => $event->getEmail(),
            'ip' => $event->getIp(),
            'userAgent' => $event->getUserAgent(),
        ]);
    }

    public function onLoginValidated(UserLoginValidatedEvent $event): void
    {
        $this->logger->info('Login validated', [
            'eventId' => $event->getEventId(),
            'userId' => $event->getUserId(),
            'email' => $event->getEmail(),
            'ip' => $event->getIp(),
        ]);
    }

    public function onAuthenticationSuccessful(UserAuthenticationSuccessfulEvent $event): void
    {
        $this->logger->info('Authentication successful', [
            'eventId' => $event->getEventId(),
            'userId' => $event->getUserId(),
            'email' => $event->getEmail(),
            'ip' => $event->getIp(),
        ]);
    }

    public function onLoggedIn(UserLoggedInEvent $event): void
    {
        $this->logger->info('User logged in', [
            'eventId' => $event->getEventId(),
            'userId' => $event->getUserId(),
            'email' => $event->getEmail(),
            'sessionId' => $event->getSessionId(),
            'rememberMe' => $event->isRememberMe(),
            'ip' => $event->getIp(),
        ]);
    }

    public function onAuthenticationFailed(UserAuthenticationFailedEvent $event): void
    {
        $this->logger->warning('Authentication failed', [
            'eventId' => $event->getEventId(),
            'email' => $event->getEmail(),
            'reason' => $event->getReason(),
            'ip' => $event->getIp(),
        ]);
    }

    public function onLoggedOut(UserLoggedOutEvent $event): void
    {
        $this->logger->info('User logged out', [
            'eventId' => $event->getEventId(),
            'userId' => $event->getUserId(),
            'email' => $event->getEmail(),
            'sessionId' => $event->getSessionId(),
            'ip' => $event->getIp(),
        ]);
    }
}
