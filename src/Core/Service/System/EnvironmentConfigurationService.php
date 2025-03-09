<?php

namespace App\Core\Service\System;

class EnvironmentConfigurationService
{
    private string $envFilePath;

    public function __construct(?string $envFilePath = null)
    {
        $this->envFilePath = $envFilePath ?? dirname(__DIR__, 4) . '/.env';
    }

    public function writeToEnvFile(string $pattern, string $value): bool
    {
        if ($this->fileExists($this->envFilePath)) {
            $envContents = $this->fileGetContents($this->envFilePath);

            if (preg_match($pattern, $envContents)) {
                $envContents = preg_replace($pattern, $value, $envContents);
            } else {
                $envContents .= PHP_EOL . $value . PHP_EOL;
            }

            $this->filePutContents($this->envFilePath, $envContents);
            return true;
        } else {
            return false;
        }
    }

    public function getEnvValue(string $pattern): string
    {
        if ($this->fileExists($this->envFilePath)) {
            $envContents = $this->fileGetContents($this->envFilePath);

            if (preg_match($pattern, $envContents, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }

    protected function fileExists(string $filePath): bool
    {
        return file_exists($filePath);
    }

    protected function fileGetContents(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    protected function filePutContents(string $filePath, string $contents): void
    {
        file_put_contents($filePath, $contents);
    }
}
