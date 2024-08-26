<?php

namespace App\Core\Service;

use Symfony\Component\Finder\Finder;

class LocaleService
{
    private string $translationPath;

    public function __construct()
    {
        $this->translationPath = __DIR__ . '/../Resources/translations';
    }

    public function getAvailableLocales(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->translationPath)->name('*.yaml');

        $locales = [];
        foreach ($finder as $file) {
            $locale = $file->getFilenameWithoutExtension();
            $locale = explode('.', $locale);
            $locale = end($locale);
            if (!in_array($locale, $locales)) {
                $locales[strtoupper($locale)] = $locale;
            }
        }

        return $locales;
    }
}