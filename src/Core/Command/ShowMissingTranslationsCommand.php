<?php

namespace App\Core\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:show-missing-translations',
    description: 'Show missing translations in the files.'
)]
class ShowMissingTranslationsCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('mainFile', InputArgument::REQUIRED, 'Main file to compare with.');
        $this->addArgument('compareFile', InputArgument::REQUIRED, 'File to compare with the main file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mainFile = $input->getArgument('mainFile');
        $compareFile = $input->getArgument('compareFile');

        if (!file_exists($mainFile)) {
            $io->error(sprintf('Main file "%s" does not exist.', $mainFile));
            return Command::FAILURE;
        }

        if (!file_exists($compareFile)) {
            $io->error(sprintf('Compare file "%s" does not exist.', $compareFile));
            return Command::FAILURE;
        }

        try {
            $mainData = Yaml::parseFile($mainFile);
            $compareData = Yaml::parseFile($compareFile);
        } catch (\Exception $e) {
            $io->error('Error parsing YAML files: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $missingKeys = $this->findMissingKeys($mainData, $compareData);

        if (empty($missingKeys)) {
            $io->success('No missing translations found.');
        } else {
            $io->warning(sprintf('Missing translations found in "%s":', $compareFile));
            $io->block(implode(PHP_EOL, $missingKeys));
        }

        return Command::SUCCESS;
    }

    private function findMissingKeys(array $mainData, array $compareData): array
    {
        $missingKeys = [];

        foreach ($mainData as $key => $value) {
            if (is_array($value)) {
                $subKeys = $this->findMissingKeys($value, $compareData[$key] ?? []);
                if (!empty($subKeys)) {
                    $missingKeys[] = implode(', ', array_map(fn($subKey) => $key . '.' . $subKey, $subKeys));
                }
            }
            if (!array_key_exists($key, $compareData)) {
                $missingKeys[] = $key;
            }
        }

        return $missingKeys;
    }
}
