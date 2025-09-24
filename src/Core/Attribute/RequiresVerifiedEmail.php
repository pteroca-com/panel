<?php

namespace App\Core\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequiresVerifiedEmail
{
    public function __construct(
        public readonly string $redirectRoute = 'verify_notice'
    ) {}
}
