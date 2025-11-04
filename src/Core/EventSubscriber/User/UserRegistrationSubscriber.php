<?php

namespace App\Core\EventSubscriber\User;

use App\Core\Event\User\Registration\UserRegisteredEvent;
use App\Core\Service\User\UserEmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserRegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserEmailService $userEmailService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::class => 'onUserRegistered',
        ];
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        if (!$event->isVerified()) {
            $this->userEmailService->sendVerificationEmail($event->getUserId(), $event->getEmail());
        }
    }
}
