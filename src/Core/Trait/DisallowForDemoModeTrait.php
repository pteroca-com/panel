<?php

namespace App\Core\Trait;

use App\Core\Exception\NotAllowedInDemoModeException;

trait DisallowForDemoModeTrait
{
    protected function disallowForDemoMode(): void
    {
        if ($this->isDemoMode()) {
            throw new NotAllowedInDemoModeException();
        }
    }

    protected function isDemoMode(): bool
    {
        return isset($_ENV['DEMO_MODE']) && $_ENV['DEMO_MODE'] === 'true';
    }
}
