<?php

namespace App\Core\Service\Update;

use App\Core\Service\Update\Operation\GitOperationService;
use App\Core\Service\Update\Operation\DatabaseOperationService;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class UpdateRollbackService
{
    private SymfonyStyle $io;
    private array $options = [];
    private array $rollbackActions = [];
    private ?string $backupPath = null;
    private array $initialState = [];

    private GitOperationService $gitService;
    private DatabaseOperationService $databaseService;
    private BackupService $backupService;
    private SystemStateManager $stateManager;

    public function __construct(
        GitOperationService $gitService,
        DatabaseOperationService $databaseService,
        BackupService $backupService,
        SystemStateManager $stateManager
    ) {
        $this->gitService = $gitService;
        $this->databaseService = $databaseService;
        $this->backupService = $backupService;
        $this->stateManager = $stateManager;
    }

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function setBackupPath(?string $backupPath): self
    {
        $this->backupPath = $backupPath;
        return $this;
    }

    public function setInitialState(array $initialState): self
    {
        $this->initialState = $initialState;
        return $this;
    }

    public function addRollbackAction(callable $action): void
    {
        $this->rollbackActions[] = $action;
    }

    public function performCompleteRollback(): void
    {
        $this->io->section('Performing Complete System Rollback');

        try {
            foreach (array_reverse($this->rollbackActions) as $rollbackAction) {
                try {
                    $rollbackAction();
                } catch (\Exception $e) {
                    $this->io->warning('Rollback action failed: ' . $e->getMessage());
                }
            }

            if ($this->backupPath && $this->backupService->validateBackup($this->backupPath)) {
                if ($this->io->confirm('Restore database from backup?', false)) {
                    $this->restoreDatabaseFromBackup();
                }
            }

            $this->restoreGitState();

            if ($this->gitService->isStashed()) {
                $this->gitService->applyStashedChanges();
            }

            $this->clearCacheForRollback();
            $this->io->warning('System rollback completed. Please verify system state manually.');
        } catch (\Exception $e) {
            $this->io->error('Rollback failed: ' . $e->getMessage());
            $this->io->text('Manual intervention may be required.');
            
            if ($this->backupPath) {
                $this->io->note("Database backup available at: {$this->backupPath}");
            }
        }
    }

    public function performSimpleRollback(): void
    {
        $this->io->note('Attempting to rollback changes...');
        
        try {
            $this->gitService->rollbackGitChanges();
            $this->rollbackActions = [];
        } catch (\Exception $e) {
            $this->io->error('Simple rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rollbackGit(): void
    {
        $this->gitService->rollbackGitChanges();
    }

    public function rollbackDatabase(string $targetVersion = null): void
    {
        try {
            $this->databaseService->rollbackMigrations($targetVersion);
        } catch (\Exception $e) {
            $this->io->warning('Database rollback failed: ' . $e->getMessage());
        }
    }

    public function rollbackComposer(): void
    {
        try {
            exec('git checkout HEAD -- composer.lock', $output, $returnCode);
            
            if ($returnCode === 0) {
                exec('composer install --no-interaction', $composerOutput, $composerCode);
                
                if ($composerCode === 0) {
                    $this->io->success('Composer dependencies restored from git.');
                } else {
                    $this->io->warning('Could not restore composer dependencies.');
                }
            }
        } catch (\Exception $e) {
            $this->io->warning('Composer rollback failed: ' . $e->getMessage());
        }
    }

    public function canRollback(): bool
    {
        return $this->gitService->canRollbackGit() || 
               $this->databaseService->canRollbackMigrations() ||
               !empty($this->initialState);
    }

    public function getRollbackSummary(): array
    {
        $summary = [
            'git_available' => $this->gitService->canRollbackGit(),
            'database_available' => $this->databaseService->canRollbackMigrations(),
            'backup_available' => $this->backupPath && $this->backupService->validateBackup($this->backupPath),
            'rollback_actions_count' => count($this->rollbackActions),
            'has_initial_state' => !empty($this->initialState)
        ];

        return $summary;
    }

    private function restoreDatabaseFromBackup(): void
    {
        try {
            $this->io->text('Restoring database from backup...');
            $this->backupService->restoreDatabaseBackup($this->backupPath);
            $this->io->success('Database restored from backup.');
        } catch (\Exception $e) {
            $this->io->error('Failed to restore database from backup: ' . $e->getMessage());
        }
    }

    private function restoreGitState(): void
    {
        if (!empty($this->initialState) && isset($this->initialState['git_commit'])) {
            if ($this->stateManager->canRollbackTo($this->initialState)) {
                $this->io->text('Restoring Git state...');
                $process = new Process(['git', 'reset', '--hard', $this->initialState['git_commit']]);
                $process->setTimeout($this->options['timeout'] ?? 600);
                $process->run();
                
                if ($process->isSuccessful()) {
                    $this->io->success('Git state restored.');
                } else {
                    $this->io->warning('Could not restore Git state: ' . $process->getErrorOutput());
                }
            }
        }
    }

    private function clearCacheForRollback(): void
    {
        try {
            $process = new Process(['php', 'bin/console', 'cache:clear']);
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->io->text('Cache cleared after rollback.');
            }
        } catch (\Exception $e) {
            $this->io->warning('Could not clear cache after rollback: ' . $e->getMessage());
        }
    }
}
