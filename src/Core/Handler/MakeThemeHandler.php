<?php

namespace App\Core\Handler;

use App\Core\Service\Template\MakeThemeService;

class MakeThemeHandler implements HandlerInterface
{
    private array $themeMetadata = [];

    public function __construct(
        private readonly MakeThemeService $makeThemeService,
    ) {}

    public function handle(): void
    {
        $this->makeThemeService->createTheme($this->themeMetadata);
    }

    public function setThemeMetadata(array $metadata): void
    {
        $this->themeMetadata = $metadata;
    }
}
