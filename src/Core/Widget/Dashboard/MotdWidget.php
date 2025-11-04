<?php

namespace App\Core\Widget\Dashboard;

use App\Core\Contract\Widget\WidgetInterface;
use App\Core\Enum\SettingEnum;
use App\Core\Enum\WidgetContext;
use App\Core\Enum\WidgetPosition;
use App\Core\Service\SettingService;

readonly class MotdWidget implements WidgetInterface
{
    public function __construct(
        private SettingService $settingService
    ) {}

    public function getName(): string
    {
        return 'motd';
    }

    public function getDisplayName(): string
    {
        return 'Message of the Day';
    }

    public function getSupportedContexts(): array
    {
        return [WidgetContext::DASHBOARD];
    }

    public function getPosition(): WidgetPosition
    {
        return WidgetPosition::RIGHT;
    }

    public function getPriority(): int
    {
        return 90; // Below balance (100), show second
    }

    public function getTemplate(): string
    {
        return 'panel/dashboard/components/motd.html.twig';
    }

    public function getData(WidgetContext $context, array $contextData): array
    {
        return [
            'motdEnabled' => $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_ENABLED->value),
            'motdTitle' => $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_TITLE->value),
            'motdMessage' => $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_MESSAGE->value),
        ];
    }

    public function isVisible(WidgetContext $context, array $contextData): bool
    {
        if ($context !== WidgetContext::DASHBOARD) {
            return false;
        }

        // Show only if MOTD is enabled in settings
        $motdEnabled = $this->settingService->getSetting(SettingEnum::CUSTOMER_MOTD_ENABLED->value);
        return (bool) $motdEnabled;
    }

    public function getColumnSize(): int
    {
        return 12; // Full width within RIGHT position
    }
}
