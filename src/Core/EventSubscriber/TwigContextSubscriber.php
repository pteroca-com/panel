<?php

namespace App\Core\EventSubscriber;

use App\Core\Service\Template\TemplateContextManager;
use App\Core\Service\Template\TemplateService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

readonly class TwigContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private TemplateContextManager $contextManager,
        private string $templatesBaseDir,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 15], // After routing (priority 32)
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $loader = $this->twig->getLoader();
        if (!$loader instanceof FilesystemLoader) {
            return;
        }

        $context = $this->contextManager->getCurrentContext();
        $theme = $this->contextManager->getThemeForContext($context);

        // Fallback to default theme if configured theme doesn't exist
        if (!is_dir("$this->templatesBaseDir/$theme")) {
            $theme = TemplateService::DEFAULT_THEME;
        }

        match ($context) {
            'landing' => $this->registerLandingPaths($loader, $theme),
            'email' => $this->registerEmailPaths($loader, $theme),
            'panel' => $this->registerPanelPaths($loader, $theme),
            default => null,
        };
    }

    /**
     * Register standard template paths for a context (landing, email, panel)
     *
     * Path priority (last added = highest priority):
     * 1. Default theme context path (fallback, searched last)
     * 2. Current theme context path (searched first)
     */
    private function registerStandardContextPaths(
        FilesystemLoader $loader,
        string $theme,
        string $context
    ): void {
        // Add default theme as fallback first (searched last)
        $defaultTheme = TemplateService::DEFAULT_THEME;
        $defaultContextPath = "$this->templatesBaseDir/$defaultTheme/$context";
        if ($theme !== $defaultTheme && is_dir($defaultContextPath)) {
            $loader->prependPath($defaultContextPath);
        }

        // Then add current theme (searched first)
        $themeContextPath = "$this->templatesBaseDir/$theme/$context";
        if (is_dir($themeContextPath)) {
            $loader->prependPath($themeContextPath);
        }
    }

    /**
     * Register template paths for landing context
     */
    private function registerLandingPaths(FilesystemLoader $loader, string $theme): void
    {
        $this->registerStandardContextPaths($loader, $theme, 'landing');
    }

    /**
     * Register template paths for email context
     */
    private function registerEmailPaths(FilesystemLoader $loader, string $theme): void
    {
        $this->registerStandardContextPaths($loader, $theme, 'email');
    }

    /**
     * Register template paths for panel context with legacy support
     */
    private function registerPanelPaths(FilesystemLoader $loader, string $theme): void
    {
        // Standard panel paths (new structure)
        $this->registerStandardContextPaths($loader, $theme, 'panel');

        // DEPRECATED: Legacy location support (will be removed in v0.8.0+)
        $this->registerPanelLegacyPaths($loader, $theme);

        // EasyAdmin bundle paths (both new and legacy)
        $this->registerPanelEasyAdminPaths($loader, $theme);
    }

    /**
     * DEPRECATED: Register legacy panel paths (themes/{theme}/)
     *
     * This method will be REMOVED in v0.8.0+
     * Legacy themes store templates directly in theme root instead of panel/ subdirectory
     * ACTION REQUIRED: Migrate your custom templates to themes/{theme}/panel/ structure
     */
    private function registerPanelLegacyPaths(FilesystemLoader $loader, string $theme): void
    {
        // Add default theme legacy location as fallback first
        $defaultTheme = TemplateService::DEFAULT_THEME;
        if ($theme !== $defaultTheme && is_dir("$this->templatesBaseDir/$defaultTheme")) {
            $loader->prependPath("$this->templatesBaseDir/$defaultTheme");
        }

        // Then add current theme legacy location
        if (is_dir("$this->templatesBaseDir/$theme")) {
            $loader->prependPath("$this->templatesBaseDir/$theme");
        }
    }

    /**
     * Register EasyAdmin bundle paths for panel context
     * Supports both new (panel/bundles/EasyAdminBundle) and legacy (bundles/EasyAdminBundle) locations
     */
    private function registerPanelEasyAdminPaths(FilesystemLoader $loader, string $theme): void
    {
        $defaultTheme = TemplateService::DEFAULT_THEME;
        $panelPath = "$this->templatesBaseDir/$theme/panel";
        $defaultPanelPath = "$this->templatesBaseDir/$defaultTheme/panel";

        // Add default theme EasyAdmin bundle as fallback first
        $defaultEasyAdminPath = "$defaultPanelPath/bundles/EasyAdminBundle";
        if ($theme !== $defaultTheme && is_dir($defaultEasyAdminPath)) {
            $loader->prependPath($defaultEasyAdminPath, 'EasyAdmin');
        }

        // Then add current theme EasyAdmin bundle
        if (is_dir("$panelPath/bundles/EasyAdminBundle")) {
            $loader->prependPath("$panelPath/bundles/EasyAdminBundle", 'EasyAdmin');
        }
        // DEPRECATED: Legacy EasyAdmin location (will be removed in v0.8.0+)
        elseif (is_dir("$this->templatesBaseDir/$theme/bundles/EasyAdminBundle")) {
            $loader->prependPath("$this->templatesBaseDir/$theme/bundles/EasyAdminBundle", 'EasyAdmin');
        }
    }
}
