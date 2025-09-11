<?php

namespace App\Core\Handler;

use App\Core\Service\Update\BackupService;
use App\Core\Service\Update\SystemStateManager;
use App\Core\Service\Update\UpdateLockManager;
use App\Core\Service\Update\ValidationService;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class UpdateSystemHandler implements HandlerInterface
{
    private bool $hasError = false;
    private bool $isStashed = false;
    private ?string $backupPath = null;
    private array $initialState = [];
    private array $rollbackActions = [];

    private SymfonyStyle $io;
    private UpdateLockManager $lockManager;
    private BackupService $backupService;
    private SystemStateManager $stateManager;
    private ValidationService $validationService;
    private Filesystem $filesystem;

    private array $options = [
        'force-composer' => false,
        'dry-run' => false,
        'verbose' => false,
        'skip-backup' => false,
        'backup-retention' => 7,
        'timeout' => 600,
    ];

    public function __construct(
        Connection $connection,
        UpdateLockManager $lockManager = null,
        BackupService $backupService = null,
        SystemStateManager $stateManager = null,
        ValidationService $validationService = null,
        Filesystem $filesystem = null
    ) {
        $this->lockManager = $lockManager ?? new UpdateLockManager();
        $this->backupService = $backupService ?? new BackupService($connection);
        $this->stateManager = $stateManager ?? new SystemStateManager();
        $this->validationService = $validationService ?? new ValidationService($connection);
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function handle(): void
    {
        try {
            // Acquire update lock first
            $this->lockManager->acquireLock();

            if ($this->options['dry-run']) {
                $this->io->title('DRY RUN MODE - No changes will be made');
                $this->showDryRunPreview();
                return;
            }

            // Pre-flight validation
            $this->executeAtomicStep('Validating update environment', fn() => $this->validateEnvironment());
            
            // Capture initial system state
            $this->executeAtomicStep('Capturing system state', fn() => $this->captureInitialState());

            // Create backup unless skipped
            if (!$this->options['skip-backup']) {
                $this->executeAtomicStep('Creating database backup', fn() => $this->createBackup());
            }

            // Show warning and confirm
            $this->showWarningMessage();

            // Execute update steps with rollback capability
            $this->executeAtomicStep('Configuring Git environment', fn() => $this->ensureGitSafeDirectory());
            $this->executeAtomicStep('Stashing local changes', fn() => $this->stashGitChanges());
            $this->executeAtomicStep('Pulling changes from repository', fn() => $this->pullGitChanges());
            $this->executeAtomicStep('Restoring local changes', fn() => $this->applyStashedChanges());
            $this->executeAtomicStep('Installing Composer dependencies', fn() => $this->composerInstall());
            $this->executeAtomicStep('Updating database schema', fn() => $this->updateDatabase());
            $this->executeAtomicStep('Clearing application cache', fn() => $this->clearCache());
            $this->executeAtomicStep('Adjusting file permissions', fn() => $this->adjustFilePermissions());

            // Clear rollback actions on success
            $this->rollbackActions = [];
            $this->stateManager->clearState();

        } catch (\Exception $e) {
            $this->hasError = true;
            $this->io->error('Update failed: ' . $e->getMessage());
            
            if (!$this->options['dry-run']) {
                $this->performCompleteRollback();
            }
            
            throw $e;
        } finally {
            $this->lockManager->releaseLock();
        }
    }

    private function showDryRunPreview(): void
    {
        // Even in dry-run mode, we should configure Git safe directory for version detection
        $this->ensureGitSafeDirectory();
        
        $this->io->section('Update Process Preview');
        
        $steps = [
            '1. Check permissions and prerequisites',
            '2. Configure Git safe directory (to prevent ownership issues)',
            '3. Stash local changes',
            '4. Fetch and merge changes from origin/main',
            '5. Restore local changes',
            '6. Install/update Composer dependencies' . ($this->options['force-composer'] ? ' (with --ignore-platform-reqs)' : ''),
            '7. Run database migrations',
            '8. Clear application cache',
            '9. Adjust file permissions',
        ];

        foreach ($steps as $step) {
            $this->io->text("  $step");
        }

        $this->io->newLine();
        $this->io->note('This was a dry run. No actual changes were made.');
        $this->io->text('To perform the actual update, run the command without --dry-run option.');
    }

    private function executeStep(string $description, callable $callback): void
    {
        if ($this->options['verbose']) {
            $this->io->text("Starting: $description");
        } else {
            $this->io->write("$description... ");
        }

        $callback();

        if (!$this->options['verbose'] && !$this->hasError) {
            $this->io->writeln('<info>✓</info>');
        }
    }

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function getCurrentVersion(): string
    {
        exec('git describe --tags', $output, $returnCode);
        if ($returnCode !== 0) {
            return 'N/A';
        }

        return $output[0];
    }

    private function showWarningMessage(): void
    {
        $this->io->warning('This command will update the PteroCA system. Do not hit CTRL+C during the update process.');
        if (!$this->io->confirm('Do you want to continue?')) {
            throw new \RuntimeException('Update process aborted.');
        }
    }

    private function checkIfGitIsInstalled(): void
    {
        exec('git --version', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Git is not installed.');
        }
    }

    private function checkIfComposerIsInstalled(): void
    {
        exec('composer --version', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Composer is not installed.');
        }
    }

    private function checkPermissions(): void
    {
        $directoryToCheck = \dirname(__DIR__, 3);
        $testFile = $directoryToCheck . DIRECTORY_SEPARATOR . 'permission_test_' . uniqid() . '.tmp';

        $fp = @fopen($testFile, 'w');
        if ($fp === false) {
            throw new \RuntimeException(sprintf(
                'You do not have write permissions to the directory "%s". Run the command with sudo/as root or adjust permissions.',
                $directoryToCheck
            ));
        }

        fclose($fp);
        @unlink($testFile);
    }

    private function ensureGitSafeDirectory(): void
    {
        $currentDir = getcwd();
        if ($currentDir === false) {
            $currentDir = \dirname(__DIR__, 3);
        }
        
        if ($this->options['verbose']) {
            $this->io->text('Checking Git safe directory configuration...');
        }
        
        // Check if current directory is already in safe.directory
        exec('git config --global --get-all safe.directory 2>/dev/null', $safeDirectories, $returnCode);
        
        $needsConfig = true;
        foreach ($safeDirectories as $safeDir) {
            // Check if current directory or any parent directory is already configured
            if ($safeDir === $currentDir || $safeDir === '*') {
                $needsConfig = false;
                break;
            }
        }
        
        if ($needsConfig) {
            if ($this->options['verbose']) {
                $this->io->note("Adding {$currentDir} to Git safe directories to prevent 'dubious ownership' errors");
            }
            
            $escapedDir = escapeshellarg($currentDir);
            exec("git config --global --add safe.directory {$escapedDir}", $configOutput, $configCode);
            
            if ($configCode !== 0) {
                $this->io->warning('Could not configure Git safe directory. Some git operations may fail due to ownership issues.');
                if ($this->options['verbose'] && !empty($configOutput)) {
                    $this->io->text('Git config error: ' . implode("\n", $configOutput));
                }
            } else {
                if ($this->options['verbose']) {
                    $this->io->success('Git safe directory configured successfully.');
                }
            }
        } else {
            if ($this->options['verbose']) {
                $this->io->text('Git safe directory already configured.');
            }
        }
    }

    private function stashGitChanges(): void
    {
        $label = 'pteroca-auto-update-' . date('Ymd-His');
        exec(sprintf('git stash push -u -m %s', escapeshellarg($label)), $output, $returnCode);
        
        if ($this->options['verbose']) {
            $this->io->text('Git stash output: ' . implode("\n", $output));
        }
        
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to stash changes.');
            return;
        }

        $this->isStashed = true;
        
        if ($this->options['verbose']) {
            $this->io->success('Local changes stashed successfully.');
        }
    }

    private function pullGitChanges(): void
    {
        if ($this->options['verbose']) {
            $this->io->text('Fetching latest changes from origin/main...');
        }
        
        exec('git fetch origin main', $outputFetch, $codeFetch);
        
        if ($this->options['verbose'] && !empty($outputFetch)) {
            $this->io->text('Git fetch output: ' . implode("\n", $outputFetch));
        }
        
        if ($codeFetch !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to fetch changes from origin.');
            if (!empty($outputFetch)) {
                $this->io->text('Git fetch error: ' . implode("\n", $outputFetch));
            }
            $this->applyStashedChanges();
            return;
        }

        if ($this->options['verbose']) {
            $this->io->text('Attempting fast-forward merge...');
        }
        
        exec('git merge --ff-only origin/main', $outputFf, $codeFf);
        
        if ($codeFf === 0) {
            if ($this->options['verbose']) {
                $this->io->success('Fast-forward merge completed successfully.');
            }
            return; 
        }

        $this->io->warning('Fast-forward not possible. Attempting a no-ff merge...');
        
        if ($this->options['verbose']) {
            $this->io->text('Fast-forward failed, trying no-ff merge...');
        }
        
        exec('git merge --no-ff --no-edit origin/main', $outputMerge, $codeMerge);
        
        if ($codeMerge !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to merge changes. Resolve merge conflicts and try again.');
            if ($this->options['verbose'] && !empty($outputMerge)) {
                $this->io->text('Git merge error: ' . implode("\n", $outputMerge));
            }
        } else {
            if ($this->options['verbose']) {
                $this->io->success('No-ff merge completed successfully.');
            }
        }
    }

    private function applyStashedChanges(): void
    {
        if ($this->isStashed) {
            exec('git stash pop --index', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->io->warning('Could not automatically re-apply stashed changes. They were kept in the stash. Run "git stash list" and "git stash pop" manually after resolving any file conflicts.');
                return;
            }

            $this->isStashed = false;
        }
    }

    private function composerInstall(): void
    {
        $isDev = strtolower($_ENV['APP_ENV'] ?? '') === 'dev';
        
        // If --force-composer option is enabled, use --ignore-platform-reqs immediately
        if ($this->options['force-composer']) {
            $this->io->note('Using --force-composer option: installing dependencies with --ignore-platform-reqs');
            $this->composerInstallWithIgnorePlatform($isDev);
            return;
        }
        
        $composerCommand = sprintf(
            'composer install %s --optimize-autoloader --no-interaction',
            $isDev ? '' : '--no-dev',
        );

        exec($composerCommand, $output, $returnCode);
        if ($returnCode !== 0) {
            $outputString = implode("\n", $output);
            
            // Check if the error is related to platform dependencies
            if ($this->isPlatformDependencyError($outputString)) {
                $this->io->warning('Composer installation failed due to platform requirements.');
                $this->io->text('This typically happens when the server is missing PHP extensions or has different PHP version requirements.');
                
                if ($this->io->confirm('Do you want to ignore platform dependencies and continue with the update?', false)) {
                    $this->composerInstallWithIgnorePlatform($isDev);
                    return;
                }
                
                // User chose not to continue, perform rollback
                $this->io->error('Update aborted due to composer dependency issues.');
                $this->performRollback();
                return;
            }
            
            // For other composer errors, show the output and fail
            $this->hasError = true;
            $this->io->error('Failed to install composer dependencies.');
            $this->io->text('Composer output:');
            $this->io->text($outputString);
        }
    }

    private function isPlatformDependencyError(string $output): bool
    {
        $platformErrorPatterns = [
            'requires php',
            'platform requirements',
            'requires ext-',
            'Your requirements could not be resolved',
            'requires',
        ];
        
        $lowerOutput = strtolower($output);
        
        foreach ($platformErrorPatterns as $pattern) {
            if (strpos($lowerOutput, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function composerInstallWithIgnorePlatform(bool $isDev): void
    {
        $composerCommand = sprintf(
            'composer install %s --optimize-autoloader --no-interaction --ignore-platform-reqs',
            $isDev ? '' : '--no-dev',
        );

        $this->io->note('Running composer install with --ignore-platform-reqs...');
        
        exec($composerCommand, $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to install composer dependencies even with --ignore-platform-reqs.');
            $this->io->text('Composer output:');
            $this->io->text(implode("\n", $output));
            $this->performRollback();
        } else {
            $this->io->success('Composer dependencies installed successfully (with ignored platform requirements).');
            $this->io->warning('Note: Platform requirements were ignored. Make sure your server environment is compatible.');
        }
    }

    private function performRollback(): void
    {
        $this->io->note('Attempting to rollback changes...');
        
        // If we have stashed changes, restore the original state
        if ($this->isStashed) {
            $this->io->text('Restoring original files from stash...');
            exec('git reset --hard HEAD~1 2>/dev/null'); // Reset any potential merge
            exec('git stash pop --index', $stashOutput, $stashCode);
            
            if ($stashCode === 0) {
                $this->io->success('Successfully restored original files.');
                $this->isStashed = false;
            } else {
                $this->io->warning('Could not automatically restore stashed changes. They are still available in git stash.');
            }
        } else {
            // Try to reset to the previous state
            exec('git reset --hard HEAD~1 2>/dev/null');
            $this->io->text('Attempted to reset to previous git state.');
        }
        
        $this->hasError = true;
    }

    private function updateDatabase(): void
    {
        exec('php bin/console doctrine:migrations:migrate --no-interaction', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to update database.');
        }
    }

    private function clearCache(): void
    {
        exec('php bin/console cache:clear', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->hasError = true;
            $this->io->error('Failed to clear cache.');
        }
    }

    private function adjustFilePermissions(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return;
        }

        $directoryToCheck = \dirname(__DIR__, 3);
        $directoryToCheck = escapeshellarg($directoryToCheck);
        $candidateOwners = [
            'www-data:www-data',
            'nginx:nginx',
            'apache:apache',
        ];

        foreach ($candidateOwners as $candidate) {
            [$user, $group] = explode(':', $candidate);

            $exitCode = 0;
            $output = [];
            exec(sprintf('id -u %s 2>/dev/null', escapeshellarg($user)), $output, $exitCode);

            if ($exitCode === 0) {
                exec(sprintf('chown -R %s %s', escapeshellarg($candidate), $directoryToCheck));
                return;
            }
        }
    }

    private function executeAtomicStep(string $description, callable $action): void
    {
        try {
            $this->executeStep($description, $action);
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->io->error("Step failed: $description - " . $e->getMessage());
            throw $e;
        }
    }

    private function validateEnvironment(): void
    {
        $validationResults = $this->validationService->validateUpdateEnvironment();
        $summary = $this->validationService->getValidationSummary($validationResults);

        if ($this->options['verbose']) {
            $this->io->section('Environment Validation Results');
            
            foreach ($validationResults as $check => $result) {
                $icon = match ($result['status']) {
                    'ok' => '✓',
                    'warning' => '⚠',
                    'error' => '✗'
                };
                
                $this->io->text("  $icon $check: {$result['message']}");
            }
        }

        if (!$summary['can_proceed']) {
            $failedChecks = array_filter($validationResults, fn($r) => $r['status'] === 'error');
            $this->io->error('Environment validation failed. The following issues must be resolved:');
            
            foreach ($failedChecks as $check => $result) {
                $this->io->text("  • $check: {$result['message']}");
            }
            
            throw new \RuntimeException('Environment validation failed');
        }

        if ($summary['warnings'] > 0) {
            $this->io->warning("{$summary['warnings']} warning(s) found during validation.");
        }
    }

    private function captureInitialState(): void
    {
        $this->initialState = $this->stateManager->captureSystemState();
        
        if ($this->options['verbose']) {
            $this->io->text('Captured system state: ' . $this->stateManager->getStateSummary($this->initialState));
        }
    }

    private function createBackup(): void
    {
        $retentionDays = (int)($this->options['backup-retention'] ?? 7);
        $this->backupPath = $this->backupService->createDatabaseBackup($retentionDays);
        
        $backupSize = $this->backupService->getBackupSize($this->backupPath);
        $backupSizeMB = round($backupSize / 1024 / 1024, 2);
        
        if ($this->options['verbose']) {
            $this->io->success("Database backup created: {$this->backupPath} ({$backupSizeMB}MB)");
        }

        // Add rollback action for backup cleanup on failure
        $this->rollbackActions[] = function() {
            if ($this->backupPath && $this->filesystem->exists($this->backupPath)) {
                $this->io->text('Keeping database backup for manual recovery: ' . $this->backupPath);
            }
        };
    }

    private function performCompleteRollback(): void
    {
        $this->io->section('Performing Complete System Rollback');

        try {
            // Execute rollback actions in reverse order
            foreach (array_reverse($this->rollbackActions) as $rollbackAction) {
                try {
                    $rollbackAction();
                } catch (\Exception $e) {
                    $this->io->warning('Rollback action failed: ' . $e->getMessage());
                }
            }

            // Restore database backup if available
            if ($this->backupPath && $this->backupService->validateBackup($this->backupPath)) {
                if ($this->io->confirm('Restore database from backup?', false)) {
                    $this->io->text('Restoring database from backup...');
                    $this->backupService->restoreDatabaseBackup($this->backupPath);
                    $this->io->success('Database restored from backup.');
                }
            }

            // Restore git state if we have initial state
            if (!empty($this->initialState) && isset($this->initialState['git_commit'])) {
                if ($this->stateManager->canRollbackTo($this->initialState)) {
                    $this->io->text('Restoring Git state...');
                    $process = new Process(['git', 'reset', '--hard', $this->initialState['git_commit']]);
                    $process->setTimeout($this->options['timeout']);
                    $process->run();
                    
                    if ($process->isSuccessful()) {
                        $this->io->success('Git state restored.');
                    } else {
                        $this->io->warning('Could not restore Git state: ' . $process->getErrorOutput());
                    }
                }
            }

            // Restore stashed changes if any
            if ($this->isStashed) {
                $this->applyStashedChanges();
            }

            // Clear cache to ensure clean state
            $process = new Process(['php', 'bin/console', 'cache:clear']);
            $process->run();

            $this->io->warning('System rollback completed. Please verify system state manually.');
            
        } catch (\Exception $e) {
            $this->io->error('Rollback failed: ' . $e->getMessage());
            $this->io->text('Manual intervention may be required.');
            
            if ($this->backupPath) {
                $this->io->note("Database backup available at: {$this->backupPath}");
            }
        }
    }

    private function execWithTimeout(array $command, int $timeout = null): array
    {
        $timeout = $timeout ?? $this->options['timeout'];
        
        $process = new Process($command);
        $process->setTimeout($timeout);
        
        try {
            $process->run();
            
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'code' => $process->getExitCode()
            ];
            
        } catch (ProcessTimedOutException $e) {
            throw new \RuntimeException("Command timed out after {$timeout} seconds: " . implode(' ', $command));
        }
    }
}
