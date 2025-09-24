<?php

namespace App\Core\Service\Update\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;

class GitOperationService
{
    private SymfonyStyle $io;
    private array $options = [];
    private bool $isStashed = false;

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

    public function ensureGitSafeDirectory(): void
    {
        $currentDir = getcwd();
        if ($currentDir === false) {
            $currentDir = \dirname(__DIR__, 5);
        }
        
        if ($this->options['verbose'] ?? false) {
            $this->io->text('Checking Git safe directory configuration...');
        }
        
        exec('git config --global --get-all safe.directory 2>/dev/null', $safeDirectories, $returnCode);
        
        $needsConfig = true;
        foreach ($safeDirectories as $safeDir) {
            if ($safeDir === $currentDir || $safeDir === '*') {
                $needsConfig = false;
                break;
            }
        }
        
        if ($needsConfig) {
            if ($this->options['verbose'] ?? false) {
                $this->io->note("Adding {$currentDir} to Git safe directories to prevent 'dubious ownership' errors");
            }
            
            $escapedDir = escapeshellarg($currentDir);
            exec("git config --global --add safe.directory {$escapedDir}", $configOutput, $configCode);
            
            if ($configCode !== 0) {
                $this->io->warning('Could not configure Git safe directory. Some git operations may fail due to ownership issues.');
                if (($this->options['verbose'] ?? false) && !empty($configOutput)) {
                    $this->io->text('Git config error: ' . implode("\n", $configOutput));
                }
            } else {
                if ($this->options['verbose'] ?? false) {
                    $this->io->success('Git safe directory configured successfully.');
                }
            }
        } else {
            if ($this->options['verbose'] ?? false) {
                $this->io->text('Git safe directory already configured.');
            }
        }
    }

    public function stashChanges(): void
    {
        $label = 'pteroca-auto-update-' . date('Ymd-His');
        exec(sprintf('git stash push -u -m %s', escapeshellarg($label)), $output, $returnCode);
        
        if ($this->options['verbose'] ?? false) {
            $this->io->text('Git stash output: ' . implode("\n", $output));
        }
        
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to stash changes.');
        }

        $this->isStashed = true;
        
        if ($this->options['verbose'] ?? false) {
            $this->io->success('Local changes stashed successfully.');
        }
    }

    public function validateCurrentBranch(): void
    {
        exec('git branch --show-current 2>/dev/null', $output, $returnCode);
        
        if ($returnCode !== 0 || empty($output)) {
            throw new \RuntimeException('Cannot determine current Git branch.');
        }
        
        $currentBranch = trim($output[0]);
        
        if ($currentBranch !== 'main') {
            throw new \RuntimeException(sprintf(
                'System updates can only be performed from the "main" branch. Current branch: "%s". Please switch to main branch using: git checkout main',
                $currentBranch
            ));
        }
        
        if ($this->options['verbose'] ?? false) {
            $this->io->success('Branch validation passed. Currently on main branch.');
        }
    }

    public function pullChanges(): void
    {
        $this->validateCurrentBranch();
        
        if ($this->options['verbose'] ?? false) {
            $this->io->text('Fetching latest changes from origin/main...');
        }
        
        exec('git fetch origin main', $outputFetch, $codeFetch);
        
        if (($this->options['verbose'] ?? false) && !empty($outputFetch)) {
            $this->io->text('Git fetch output: ' . implode("\n", $outputFetch));
        }
        
        if ($codeFetch !== 0) {
            $this->applyStashedChanges();
            throw new \RuntimeException('Failed to fetch changes from origin. ' . implode("\n", $outputFetch));
        }

        if ($this->options['verbose'] ?? false) {
            $this->io->text('Attempting fast-forward merge...');
        }
        
        exec('git merge --ff-only origin/main', $outputFf, $codeFf);
        
        if ($codeFf === 0) {
            if ($this->options['verbose'] ?? false) {
                $this->io->success('Fast-forward merge completed successfully.');
            }
            return; 
        }

        $this->io->warning('Fast-forward not possible. Attempting a no-ff merge...');
        
        if ($this->options['verbose'] ?? false) {
            $this->io->text('Fast-forward failed, trying no-ff merge...');
        }
        
        exec('git merge --no-ff --no-edit origin/main', $outputMerge, $codeMerge);
        
        if ($codeMerge !== 0) {
            throw new \RuntimeException('Failed to merge changes. Resolve merge conflicts and try again. ' . implode("\n", $outputMerge));
        }

        if ($this->options['verbose'] ?? false) {
            $this->io->success('No-ff merge completed successfully.');
        }
    }

    public function applyStashedChanges(): void
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

    public function canRollbackGit(): bool
    {
        return $this->isStashed;
    }

    public function rollbackGitChanges(): void
    {
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
    }

    public function isStashed(): bool
    {
        return $this->isStashed;
    }
}
