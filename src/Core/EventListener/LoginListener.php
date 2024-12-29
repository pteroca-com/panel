<?php

namespace App\Core\EventListener;

use App\Core\Enum\LogActionEnum;
use App\Core\Service\Logs\LogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LoginListener implements EventSubscriberInterface
{
    public function __construct(
        private LogService $logService,
    ) {}

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $this->logService->logAction($user, LogActionEnum::LOGIN);
    }

    public function onSecurityLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()->getUser();
        $this->logService->logAction($user, LogActionEnum::LOGOUT);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onSecurityInteractiveLogin',
            LogoutEvent::class => 'onSecurityLogout',
        ];
    }
}
