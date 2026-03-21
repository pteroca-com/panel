<?php

namespace App\Core\Service;

class PriceFormatterService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function formatPrice(float $price): string
    {
        [$decimalSep, $thousandsSep] = $this->getSeparators();

        return number_format($price, 2, $decimalSep, $thousandsSep);
    }

    public function getSeparators(): array
    {
        $format = $this->settingService->getSetting('price_format') ?? ',| ';
        $parts = explode('|', $format, 2);

        return [
            $parts[0] ?? ',',
            $parts[1] ?? ' ',
        ];
    }
}
