<?php

namespace App\Core\Trait;

trait FormatBytesTrait
{
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        return sprintf("%.{$precision}f %s", $bytes / (1024 ** $factor), $units[$factor]);
    }

}