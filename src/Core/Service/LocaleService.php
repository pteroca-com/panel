<?php

namespace App\Core\Service;

use App\Core\Enum\LanguageEnum;
use Symfony\Component\Finder\Finder;

class LocaleService
{
    private string $translationPath;

    public function __construct()
    {
        $this->translationPath = __DIR__ . '/../Resources/translations';
    }

    public function getAvailableLocales(bool $uppercase = true): array
    {
        $finder = new Finder();
        $finder->files()->in($this->translationPath)->name('*.yaml');

        $locales = [];
        foreach ($finder as $file) {
            $locale = $file->getFilenameWithoutExtension();
            $locale = explode('.', $locale);
            $locale = end($locale);
            if (!in_array($locale, $locales)) {
                $localeIndex = $uppercase ? strtoupper($locale) : $locale;
                $locales[$localeIndex] = $this->prepareLocaleTranslation($locale);
            }
        }

        return $locales;
    }

    private function prepareLocaleTranslation(string $locale): string
    {
        $localeTranslation = LanguageEnum::tryFrom($locale)?->name ?? $locale;

        if (str_contains($localeTranslation, '_')) {
            $parts = explode('_', $localeTranslation);
            $mainPart = $parts[0];
            $regionPart = $parts[1];
            $localeTranslation = $mainPart . ' (' . $regionPart . ')';
        }

        return $localeTranslation;
    }
}
