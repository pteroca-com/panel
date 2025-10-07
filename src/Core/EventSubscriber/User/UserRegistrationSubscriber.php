<?php

namespace App\Core\EventSubscriber\User;

use App\Core\Event\User\Registration\UserRegisteredEvent;
use App\Core\Event\User\Registration\UserRegistrationFailedEvent;
use App\Core\Service\User\UserEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserEmailService $userEmailService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::class => 'onUserRegistered',
            UserRegistrationFailedEvent::class => 'onUserRegistrationFailed',
        ];
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        if (!$event->isVerified()) {
            $this->userEmailService->sendVerificationEmail($event->getUserId(), $event->getEmail());
        }
    }

    public function onUserRegistrationFailed(UserRegistrationFailedEvent $event): void
    {
        $this->logger->error('User registration failed', [
            'email' => $event->getEmail(),
            'reason' => $event->getReason(),
            'stage' => $event->getStage(),
            'eventId' => $event->getEventId(),
        ]);
    }
}
