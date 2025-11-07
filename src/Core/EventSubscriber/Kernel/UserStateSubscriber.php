<?php

namespace App\Core\EventSubscriber\Kernel;

use App\Core\Contract\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UserStateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }


        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        if ($user->isDeleted()) {
            $this->security->logout(false);

            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans('pteroca.login.account_deleted')
            );
        }

        if ($user->isBlocked()) {
            $this->security->logout(false);

            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans('pteroca.login.user_blocked')
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }
}
