<?php

namespace App\Core\Command\Theme;

use App\Core\Service\SettingService;
use App\Core\Service\Template\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pteroca:theme:reset',
    description: 'Reset active theme to default for one or all contexts',
    aliases: ['theme:reset']
)]
class ThemeResetCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly SettingService $settingService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'context',
                InputArgument::OPTIONAL,
                'Context to reset (panel, landing, email). If omitted, resets all contexts.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompt'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = $input->getArgument('context');
        $force = $input->getOption('force');

        if ($context !== null && !array_key_exists($context, TemplateService::CONTEXT_SETTING_MAP)) {
            $io->error("Invalid context '$context'. Valid contexts: panel, landing, email");
            return Command::FAILURE;
        }

        $contextsToReset = $context !== null
            ? [$context]
            : array_keys(TemplateService::CONTEXT_SETTING_MAP);

        $io->title('Reset Theme' . (count($contextsToReset) > 1 ? 's' : '') . ' to Default');

        if (!$this->templateService->themeExists(TemplateService::DEFAULT_THEME)) {
            $io->error("Default theme '" . TemplateService::DEFAULT_THEME . "' not found in filesystem.");
            return Command::FAILURE;
        }

        $changes = [];
        foreach ($contextsToReset as $ctx) {
            $settingEnum = TemplateService::CONTEXT_SETTING_MAP[$ctx];
            $currentTheme = $this->settingService->getSetting($settingEnum->value) ?? TemplateService::DEFAULT_THEME;

            if ($currentTheme === TemplateService::DEFAULT_THEME) {
                $io->text("Context '$ctx' is already using the default theme.");
                continue;
            }

            if (!$this->templateService->themeSupportsContext(TemplateService::DEFAULT_THEME, $ctx)) {
                $io->warning("Default theme does not support '$ctx' context. Skipping.");
                continue;
            }

            $changes[] = [
                'context' => $ctx,
                'current' => $currentTheme,
                'settingEnum' => $settingEnum,
            ];
        }

        if (empty($changes)) {
            $io->success('All contexts are already using the default theme. Nothing to do.');
            return Command::SUCCESS;
        }

        $io->section('Planned Changes');
        $io->table(
            ['Context', 'Current Theme', 'New Theme'],
            array_map(fn($c) => [$c['context'], $c['current'], TemplateService::DEFAULT_THEME], $changes)
        );

        if (!$force && !$io->confirm('Proceed with reset?', false)) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            foreach ($changes as $change) {
                $this->settingService->saveSetting($change['settingEnum']->value, TemplateService::DEFAULT_THEME);
                $io->text("Reset '{$change['context']}' theme: {$change['current']} → " . TemplateService::DEFAULT_THEME);
            }

            $io->success(sprintf(
                'Successfully reset %d context(s) to default theme.',
                count($changes)
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to reset theme: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
