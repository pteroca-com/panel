<?php

namespace App\Core\Command\Plugin;

use App\Core\Exception\Plugin\InvalidStateTransitionException;
use App\Core\Service\Plugin\PluginManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'plugin:disable',
    description: 'Disable a plugin',
)]
class PluginDisableCommand extends Command
{
    private PluginManager $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        parent::__construct();
        $this->pluginManager = $pluginManager;
    }

    protected function configure(): void
    {
        $this->addArgument(
            'plugin',
            InputArgument::REQUIRED,
            'Plugin name to disable'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pluginName = $input->getArgument('plugin');

        $io->title("Disable Plugin: {$pluginName}");

        $plugin = $this->pluginManager->getPluginByName($pluginName);
        if ($plugin === null) {
            $io->error("Plugin '{$pluginName}' not found. Run 'plugin:list' to see available plugins.");

            return Command::FAILURE;
        }

        if ($plugin->getState()->value === 'disabled') {
            $io->warning("Plugin '{$pluginName}' is already disabled");

            return Command::SUCCESS;
        }

        $io->section('Plugin Information');
        $io->table(
            ['Property', 'Value'],
            [
                ['Name', $plugin->getName()],
                ['Display Name', $plugin->getDisplayName()],
                ['Version', $plugin->getVersion()],
                ['Author', $plugin->getAuthor()],
                ['Current State', $plugin->getState()->getLabel()],
            ]
        );

        try {
            $this->pluginManager->disablePlugin($plugin);

            $io->success("Plugin '{$pluginName}' has been disabled successfully");

            return Command::SUCCESS;
        } catch (InvalidStateTransitionException $e) {
            $io->error($e->getMessage());
            $io->note("Current state: {$plugin->getState()->getLabel()}");

            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error("Failed to disable plugin: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
