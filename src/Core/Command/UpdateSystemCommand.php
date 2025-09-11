<?php

namespace App\Core\Command;

use App\Core\Handler\UpdateSystemHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-system',
    description: 'Update system command',
)]
class UpdateSystemCommand extends Command
{
    public function __construct(
        private readonly UpdateSystemHandler $updateSystemHandler
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force-composer',
                null,
                InputOption::VALUE_NONE,
                'Force composer install with --ignore-platform-reqs without asking'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be done without making any changes'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $options = [
            'force-composer' => $input->getOption('force-composer'),
            'dry-run' => $input->getOption('dry-run'),
            'verbose' => $input->getOption('verbose'),
        ];
        
        $this->updateSystemHandler
            ->setIo($io)
            ->setOptions($options)
            ->handle();

        if ($this->updateSystemHandler->hasError()) {
            $io->error(sprintf(
                'Update process has been finished with errors. Current version: %s',
                $this->updateSystemHandler->getCurrentVersion(),
            ));

            return Command::FAILURE;
        } else {
            $io->success(sprintf(
                'Update process has been finished successfully. Current version: %s',
                $this->updateSystemHandler->getCurrentVersion(),
            ));

            return Command::SUCCESS;
        }
    }
}
