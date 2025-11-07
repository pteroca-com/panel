<?php

namespace App\Core\EventSubscriber\Security;

use App\Core\Entity\User;
use App\Core\Enum\LogActionEnum;
use App\Core\Service\Logs\LogService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use App\Core\Event\User\Authentication\UserLoggedInEvent;
use App\Core\Event\User\Authentication\UserLoggedOutEvent;
use App\Core\Event\User\Authentication\UserLogoutRequestedEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Core\Event\User\Authentication\UserAuthenticationFailedEvent;
use App\Core\Event\User\Authentication\UserAuthenticationSuccessfulEvent;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

#[AsEventListener(event: LoginSuccessEvent::class)]
#[AsEventListener(event: LoginFailureEvent::class)]
#[AsEventListener(event: LogoutEvent::class)]
class AuthenticationSubscriber
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack             $requestStack,
        private LogService               $logService,
    ) {}

    public function __invoke(LoginSuccessEvent|LoginFailureEvent|LogoutEvent $event): void
    {
        match (true) {
            $event instanceof LoginSuccessEvent => $this->onLoginSuccess($event),
            $event instanceof LoginFailureEvent => $this->onLoginFailure($event),
            $event instanceof LogoutEvent => $this->onLogout($event),
        };
    }

    private function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $context = [
            'ip' => $request?->getClientIp(),
            'userAgent' => $request?->headers->get('User-Agent'),
            'locale' => $request?->getLocale(),
        ];

        // Log to LogService (przeniesione z LoginListener)
        $this->logService->logAction($user, LogActionEnum::LOGIN);

        // Emit UserAuthenticationSuccessfulEvent
        $authSuccessEvent = new UserAuthenticationSuccessfulEvent(
            $user->getId(),
            $user->getEmail(),
            $context
        );
        $this->eventDispatcher->dispatch($authSuccessEvent);

        // Emit UserLoggedInEvent
        $sessionId = $request?->getSession()->getId();
        $rememberMe = $event->getPassport()->hasBadge(RememberMeBadge::class);

        $loggedInEvent = new UserLoggedInEvent(
            $user->getId(),
            $user->getEmail(),
            $sessionId,
            $rememberMe,
            $context
        );
        $this->eventDispatcher->dispatch($loggedInEvent);
    }

    private function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $email = $request?->request->get('email', 'unknown');

        $exception = $event->getException();
        $reason = $exception->getMessage();

        $context = [
            'ip' => $request?->getClientIp(),
            'userAgent' => $request?->headers->get('User-Agent'),
        ];

        $failedEvent = new UserAuthenticationFailedEvent(
            $email,
            $reason,
            $context
        );
        $this->eventDispatcher->dispatch($failedEvent);
    }

    private function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $sessionId = $request?->getSession()->getId();

        $context = [
            'ip' => $request?->getClientIp(),
            'userAgent' => $request?->headers->get('User-Agent'),
            'locale' => $request?->getLocale(),
        ];

        // Emit UserLogoutRequestedEvent (pre-event)
        $logoutRequestedEvent = new UserLogoutRequestedEvent(
            $user->getId(),
            $user->getEmail(),
            $sessionId,
            $context
        );
        $this->eventDispatcher->dispatch($logoutRequestedEvent);

        // Log to LogService (przeniesione z LoginListener)
        $this->logService->logAction($user, LogActionEnum::LOGOUT);

        // Emit UserLoggedOutEvent (post-event)
        $loggedOutEvent = new UserLoggedOutEvent(
            $user->getId(),
            $user->getEmail(),
            $sessionId,
            $context
        );
        $this->eventDispatcher->dispatch($loggedOutEvent);
    }
}
