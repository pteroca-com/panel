<?php

namespace App\Core\Service\Update;

use App\Core\Handler\HandlerInterface;
use App\Core\Service\Update\Operation\GitOperationService;
use App\Core\Service\Update\Operation\ComposerOperationService;
use App\Core\Service\Update\Operation\DatabaseOperationService;
use App\Core\Service\Update\Operation\SystemOperationService;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateOrchestrator implements HandlerInterface
{
    private bool $hasError = false;
    private ?string $backupPath = null;
    private array $initialState = [];

    private SymfonyStyle $io;
    private array $options = [
        'force-composer' => false,
        'dry-run' => false,
        'verbose' => false,
        'skip-backup' => false,
        'backup-retention' => 7,
        'timeout' => 600,
    ];

    private GitOperationService $gitService;
    private ComposerOperationService $composerService;
    private DatabaseOperationService $databaseService;
    private SystemOperationService $systemService;
    private UpdateRollbackService $rollbackService;
    private ValidationService $validationService;
    private SystemStateManager $stateManager;
    private BackupService $backupService;
    private UpdateLockManager $lockManager;

    public function __construct(
        GitOperationService $gitService,
        ComposerOperationService $composerService,
        DatabaseOperationService $databaseService,
        SystemOperationService $systemService,
        UpdateRollbackService $rollbackService,
        ValidationService $validationService,
        SystemStateManager $stateManager,
        BackupService $backupService,
        UpdateLockManager $lockManager
    ) {
        $this->gitService = $gitService;
        $this->composerService = $composerService;
        $this->databaseService = $databaseService;
        $this->systemService = $systemService;
        $this->rollbackService = $rollbackService;
        $this->validationService = $validationService;
        $this->stateManager = $stateManager;
        $this->backupService = $backupService;
        $this->lockManager = $lockManager;
    }

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;
        
        $this->gitService->setIo($io);
        $this->composerService->setIo($io);
        $this->databaseService->setIo($io);
        $this->systemService->setIo($io);
        $this->rollbackService->setIo($io);

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        $this->gitService->setOptions($this->options);
        $this->composerService->setOptions($this->options);
        $this->databaseService->setOptions($this->options);
        $this->systemService->setOptions($this->options);
        $this->rollbackService->setOptions($this->options);

        return $this;
    }

    public function handle(): void
    {
        try {
            $this->lockManager->acquireLock();

            if ($this->options['dry-run']) {
                $this->io->title('DRY RUN MODE - No changes will be made');
                $this->showDryRunPreview();
                return;
            }

            $this->executeAtomicStep('Validating update environment', fn() => $this->validateEnvironment());
            
            if ($this->hasError) {
                return;
            }
            
            $this->executeAtomicStep('Capturing system state', fn() => $this->captureInitialState());

            if (!$this->options['skip-backup']) {
                $this->executeAtomicStep('Creating database backup', fn() => $this->createBackup());
            }

            $this->showWarningMessage();

            $this->executeAtomicStep('Configuring Git environment', fn() => $this->gitService->ensureGitSafeDirectory());
            $this->executeAtomicStep('Stashing local changes', fn() => $this->gitService->stashChanges());
            $this->executeAtomicStep('Pulling changes from repository', fn() => $this->gitService->pullChanges());
            $this->executeAtomicStep('Restoring local changes', fn() => $this->gitService->applyStashedChanges());
            $this->executeAtomicStep('Installing Composer dependencies', fn() => $this->composerService->installDependencies());
            $this->executeAtomicStep('Updating database schema', fn() => $this->databaseService->runMigrations());
            $this->executeAtomicStep('Clearing application cache', fn() => $this->systemService->clearCache());
            $this->executeAtomicStep('Adjusting file permissions', fn() => $this->systemService->adjustFilePermissions());
            $this->executeAtomicStep('Restoring file ownership', fn() => $this->systemService->restoreFileOwnership());

            $this->stateManager->clearState();
        } catch (\Exception $e) {
            $this->hasError = true;
            
            if (!$this->options['dry-run']) {
                $this->rollbackService->performCompleteRollback();
            }
            
            throw $e;
        } finally {
            $this->lockManager->releaseLock();
        }
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function getCurrentVersion(): string
    {
        exec('git describe --tags 2>/dev/null', $output, $returnCode);
        if ($returnCode !== 0) {
            exec('git rev-parse --short HEAD 2>/dev/null', $output, $returnCode);
            if ($returnCode !== 0) {
                return 'Unknown';
            }
            return 'dev-' . ($output[0] ?? 'unknown');
        }
        return $output[0] ?? 'Unknown';
    }

    private function showDryRunPreview(): void
    {
        $this->gitService->ensureGitSafeDirectory();
        
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

    private function executeAtomicStep(string $description, callable $action): void
    {
        try {
            $this->executeStep($description, $action);
        } catch (\Exception $e) {
            $this->hasError = true;

            throw $e;
        }
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
                
                if (isset($result['details']) && !empty($result['details'])) {
                    if (isset($result['details']['issues']) && !empty($result['details']['issues'])) {
                        foreach ($result['details']['issues'] as $issue) {
                            $this->io->text("    → $issue");
                        }
                    }
                    if (isset($result['details']['warnings']) && !empty($result['details']['warnings'])) {
                        foreach ($result['details']['warnings'] as $warning) {
                            $this->io->text("    ⚠ $warning");
                        }
                    }
                    if (isset($result['details']['error'])) {
                        $this->io->text("    → Error: {$result['details']['error']}");
                    }
                }
            }
            
            $warningChecks = array_filter($validationResults, fn($r) => $r['status'] === 'warning');
            if (!empty($warningChecks)) {
                $this->io->newLine();
                $this->io->warning('Additional warnings found:');
                foreach ($warningChecks as $check => $result) {
                    $this->io->text("  • $check: {$result['message']}");
                }
            }
            
            throw new \RuntimeException('Environment validation failed');
        }

        if ($summary['warnings'] > 0) {
            $this->io->warning("{$summary['warnings']} warning(s) found during validation.");
            
            $warningChecks = array_filter($validationResults, fn($r) => $r['status'] === 'warning');
            foreach ($warningChecks as $check => $result) {
                $this->io->text("  • $check: {$result['message']}");
            }
        }
    }

    private function captureInitialState(): void
    {
        $this->initialState = $this->stateManager->captureSystemState();
        $this->rollbackService->setInitialState($this->initialState);
        
        if ($this->options['verbose']) {
            $this->io->text('Captured system state: ' . $this->stateManager->getStateSummary($this->initialState));
        }
    }

    private function createBackup(): void
    {
        $retentionDays = (int)($this->options['backup-retention'] ?? 7);
        $this->backupPath = $this->backupService->createDatabaseBackup($retentionDays);
        $this->rollbackService->setBackupPath($this->backupPath);
        
        $backupSize = $this->backupService->getBackupSize($this->backupPath);
        $backupSizeMB = round($backupSize / 1024 / 1024, 2);
        
        if ($this->options['verbose']) {
            $this->io->success("Database backup created: {$this->backupPath} ({$backupSizeMB}MB)");
        }

        $this->rollbackService->addRollbackAction(function() {
            if ($this->backupPath && file_exists($this->backupPath)) {
                $this->io->text('Keeping database backup for manual recovery: ' . $this->backupPath);
            }
        });
    }

    private function showWarningMessage(): void
    {
        $this->io->warning('This command will update the PteroCA system. Do not hit CTRL+C during the update process.');
        if (!$this->io->confirm('Do you want to continue?')) {
            throw new \RuntimeException('Update process aborted.');
        }
    }
}
