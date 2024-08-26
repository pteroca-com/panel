<?php

namespace App\Core\Command;

use App\Core\Handler\Installer\SystemSettingConfiguratorHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:configure-system',
    description: 'Configure system settings',
)]
class ConfigureSystemCommand extends Command
{
    public function __construct(
        private readonly SystemSettingConfiguratorHandler $systemSettingConfiguratorHandler,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->systemSettingConfiguratorHandler->configureSystemSettings($io);

        $io->success('System configured!');
        return Command::SUCCESS;
    }
}
