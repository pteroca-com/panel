<?php

namespace App\Core\DTO;

readonly class TemplateOptionsDTO
{
    public function __construct(
        private bool $supportDarkMode,
        private bool $supportCustomColors,
        private bool $forceDarkMode = false,
    ) {}

    public function isSupportDarkMode(): bool
    {
        return $this->supportDarkMode;
    }

    public function isSupportCustomColors(): bool
    {
        return $this->supportCustomColors;
    }

    /** When true, the panel is forced to Bootstrap dark mode (data-bs-theme="dark") and shared dark variables apply. */
    public function isForceDarkMode(): bool
    {
        return $this->forceDarkMode;
    }
}