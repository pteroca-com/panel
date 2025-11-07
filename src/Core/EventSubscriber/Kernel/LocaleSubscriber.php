<?php

namespace App\Core\EventSubscriber\Kernel;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SettingService $settingService,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $localeSetting = $this->settingService->getSetting(SettingEnum::LOCALE->value);

        if ($localeSetting) {
            $request->setLocale($localeSetting);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
