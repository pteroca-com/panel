<?php

namespace App\Core\Handler\Installer;

use App\Core\Service\System\EnvironmentConfigurationService;
use Symfony\Component\Console\Style\SymfonyStyle;

readonly class DatabaseConfiguratorHandler
{
    public function __construct(
        private EnvironmentConfigurationService $environmentConfigurationHandler
    )
    {
    }

    public function configureDatabase(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to configure database? (yes/no)', 'yes') === 'yes') {
            $this->askForDatabaseCredentials($io);
            $this->askForMigration($io);
        }
    }

    private function askForMigration(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to run migrations? (yes/no)', 'yes') === 'yes') {
            $io->section('Running Migrations');
            $output = null;
            $returnVar = null;

            exec('php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1', $output, $returnVar);
            if ($returnVar === 0) {
                $io->success('Migrations ran successfully.');
            } else {
                $io->error('An error occurred while running migrations.');
                $io->text(implode(PHP_EOL, $output));
            }
        }
    }

    private function askForDatabaseCredentials(SymfonyStyle $io): void
    {
        if ($io->ask('Do you want to set database credentials? (yes/no)', 'yes') === 'yes') {
            $io->section('Database configuration');
            $io->text('Please provide database credentials');
            $io->newLine();

            $databaseHost = $io->ask('Database host', '127.0.0.1');
            $databasePort = $io->ask('Database port', '3306');
            $databaseName = $io->ask('Database name', 'pteroca');
            $databaseUser = $io->ask('Database user', 'pterocauser');
            $databasePassword = $io->ask('Database password', '');
            $dsn = sprintf('DATABASE_URL=mysql://%s:%s@%s:%s/%s', $databaseUser, $databasePassword, $databaseHost, $databasePort, $databaseName);
            if (!$this->environmentConfigurationHandler->writeToEnvFile('/^DATABASE_URL=.*$/m', $dsn)) {
                $io->error('An error occurred while writing to .env file.');
            } else {
                $io->success('Database credentials set successfully.');
                $this->updateDsnServerVersion($dsn);
            }
        }
    }

    private function updateDsnServerVersion(string $dsn): void
    {
        $serverVersion = $this->getServerVersion();
        if (empty($serverVersion)) {
            return;
        }
        $updatedDsn = sprintf('%s?serverVersion=%s', $dsn, $serverVersion);
        $this->environmentConfigurationHandler->writeToEnvFile('/^DATABASE_URL=.*$/m', $updatedDsn);
    }

    private function getServerVersion(): string
    {
        $output = null;
        $returnVar = null;

        exec('php bin/console doctrine:query:sql "SELECT VERSION()" 2>&1', $output, $returnVar);

        if ($returnVar === 0 && !empty($output)) {
            foreach ($output as $line) {
                if (preg_match('/^(\d+\.\d+\.\d+(-\w+)?(-\w+)?)/', trim($line), $matches)) {
                    return $matches[1];
                }
            }
        }

        return '';
    }
}
