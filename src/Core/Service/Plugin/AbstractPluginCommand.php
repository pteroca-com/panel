<?php

namespace App\Core\Service\Plugin;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Abstract base class for plugin console commands.
 *
 * Plugin console commands should extend this class to get access to common
 * services and utilities. This class extends Symfony's Command class.
 *
 * Example usage:
 *
 * ```php
 * namespace Plugins\MyPlugin\Command;
 *
 * use App\Core\Service\Plugin\AbstractPluginCommand;
 * use Symfony\Component\Console\Input\InputInterface;
 * use Symfony\Component\Console\Output\OutputInterface;
 *
 * class MyPluginSyncCommand extends AbstractPluginCommand
 * {
 *     protected static $defaultName = 'my-plugin:sync';
 *     protected static $defaultDescription = 'Synchronize data for MyPlugin';
 *
 *     protected function execute(InputInterface $input, OutputInterface $output): int
 *     {
 *         $this->io->title('MyPlugin Data Sync');
 *
 *         // Your command logic here
 *         $this->logInfo('Starting data sync...');
 *
 *         $this->io->success('Sync completed!');
 *
 *         return Command::SUCCESS;
 *     }
 * }
 * ```
 */
abstract class AbstractPluginCommand extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Initialize the command.
     *
     * This is called before execute() and sets up the SymfonyStyle helper.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Get the plugin name from the class namespace.
     *
     * This extracts the plugin name from the fully qualified class name.
     * For example: Plugins\HelloWorld\Command\MyCommand -> hello-world
     *
     * @return string Plugin name in kebab-case
     */
    protected function getPluginName(): string
    {
        $className = static::class;

        // Extract plugin name from namespace (e.g., Plugins\HelloWorld\... -> HelloWorld)
        if (preg_match('/^Plugins\\\\([^\\\\]+)/', $className, $matches)) {
            $pluginClassName = $matches[1];

            // Convert from PascalCase to kebab-case
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $pluginClassName));
        }

        return 'unknown';
    }

    /**
     * Log an informational message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->info($message, $context);
    }

    /**
     * Log a warning message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->warning($message, $context);
    }

    /**
     * Log an error message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logError(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->error($message, $context);
    }

    /**
     * Log a debug message with plugin context.
     *
     * @param string $message
     * @param array $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $context['plugin'] = $this->getPluginName();
        $this->logger->debug($message, $context);
    }

    /**
     * Display a success message.
     *
     * @param string $message
     */
    protected function success(string $message): void
    {
        $this->io->success($message);
    }

    /**
     * Display an error message.
     *
     * @param string $message
     */
    protected function error(string $message): void
    {
        $this->io->error($message);
    }

    /**
     * Display a warning message.
     *
     * @param string $message
     */
    protected function warning(string $message): void
    {
        $this->io->warning($message);
    }

    /**
     * Display an info message.
     *
     * @param string $message
     */
    protected function info(string $message): void
    {
        $this->io->info($message);
    }
}
