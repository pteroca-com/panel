<?php

namespace App\Core\EventListener;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use App\Core\Service\LocaleService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class LocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private SettingService $settingService,
        private LocaleService $localeService,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $sync = $this->settingService->getSetting(SettingEnum::BROWSER_LANGUAGE_SYNC->value);
        $isSyncEnabled = $sync === '1' || strtolower((string)$sync) === 'true';

        if ($isSyncEnabled) {
            // Build list of supported locales (e.g. ['en','de','fr', ...])
            $available = array_keys($this->localeService->getAvailableLocales(false));
            $preferred = $request->getPreferredLanguage($available);
            $request->setLocale($preferred ?: 'en');
            return;
        }

        // Fallback to configured site locale, else English
        $localeSetting = $this->settingService->getSetting(SettingEnum::LOCALE->value) ?: 'en';
        $request->setLocale($localeSetting);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}