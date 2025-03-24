<?php

namespace App\Core\DTO;

readonly class TemplateOptionsDTO
{
    public function __construct(
        private bool $supportDarkMode,
        private bool $supportCustomColors,
    ) {}

    public function isSupportDarkMode(): bool
    {
        return $this->supportDarkMode;
    }

    public function isSupportCustomColors(): bool
    {
        return $this->supportCustomColors;
    }
}