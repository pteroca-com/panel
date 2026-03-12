<?php

namespace App\Core\Command\Theme;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use App\Core\Service\Template\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:theme:list',
    description: 'List all installed themes with context information',
    aliases: ['theme:list']
)]
class ThemeListCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly SettingService $settingService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'context',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Filter by context (panel, landing, email)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contextFilter = $input->getOption('context');

        $validContexts = array_keys(TemplateService::CONTEXT_SETTING_MAP);

        if ($contextFilter !== null && !in_array($contextFilter, $validContexts, true)) {
            $io->error("Invalid context '$contextFilter'. Valid contexts: panel, landing, email");
            return Command::FAILURE;
        }

        $io->title('Theme List');

        $panelTheme = $this->settingService->getSetting(SettingEnum::PANEL_THEME->value) ?? TemplateService::DEFAULT_THEME;
        $landingTheme = $this->settingService->getSetting(SettingEnum::LANDING_THEME->value) ?? TemplateService::DEFAULT_THEME;
        $emailTheme = $this->settingService->getSetting(SettingEnum::EMAIL_THEME->value) ?? TemplateService::DEFAULT_THEME;

        $io->section('Active Themes');
        $io->table(
            ['Context', 'Active Theme'],
            [
                ['panel', $panelTheme],
                ['landing', $landingTheme],
                ['email', $emailTheme],
            ]
        );

        if ($contextFilter !== null) {
            $activeTheme = match ($contextFilter) {
                'panel' => $panelTheme,
                'landing' => $landingTheme,
                'email' => $emailTheme,
            };
            $themes = $this->templateService->getThemesForContext($contextFilter, $activeTheme);
        } else {
            $themes = $this->templateService->getAllThemesWithActiveContexts(
                $panelTheme,
                $landingTheme,
                $emailTheme
            );
        }

        if (count($themes) === 0) {
            $io->warning('No themes found');
            return Command::SUCCESS;
        }

        $io->section('Themes' . ($contextFilter ? " (context: $contextFilter)" : ''));

        $rows = [];
        foreach ($themes as $theme) {
            $activeFor = $contextFilter !== null
                ? ($theme->isActive() ? "<fg=green>active</>" : '<fg=gray>inactive</>')
                : $this->formatActiveContexts($theme->getActiveContexts());

            $contexts = $contextFilter !== null
                ? $contextFilter
                : implode(', ', $theme->getContexts());

            $rows[] = [
                $theme->getName(),
                $theme->getVersion(),
                $theme->getAuthor(),
                $contexts,
                $activeFor,
            ];
        }

        $io->table(
            ['Name', 'Version', 'Author', 'Supported Contexts', 'Active For'],
            $rows
        );

        $io->success(sprintf('Found %d theme(s)', count($themes)));

        return Command::SUCCESS;
    }

    private function formatActiveContexts(array $activeContexts): string
    {
        if (empty($activeContexts)) {
            return '<fg=gray>none</>';
        }

        return '<fg=green>' . implode(', ', $activeContexts) . '</>';
    }
}
