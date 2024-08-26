<?php

namespace App\Core\Service\System;

class EnvironmentConfigurationService
{
    public function writeToEnvFile(string $pattern, string $value): bool
    {
        $envFilePath = dirname(__DIR__, 4) . '/.env';
        if (file_exists($envFilePath)) {
            $envContents = file_get_contents($envFilePath);

            if (preg_match($pattern, $envContents)) {
                $envContents = preg_replace($pattern, $value, $envContents);
            } else {
                $envContents .= PHP_EOL . $value . PHP_EOL;
            }

            file_put_contents($envFilePath, $envContents);
            return true;
        } else {
            return false;
        }
    }

    public function getEnvValue(string $pattern): string
    {
        $envFilePath = dirname(__DIR__, 4) . '/.env';
        if (file_exists($envFilePath)) {
            $envContents = file_get_contents($envFilePath);

            if (preg_match($pattern, $envContents, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }
}