<?php

namespace App\Core\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
readonly class RequiresVerifiedEmail
{
    public function __construct(
        public string $redirectRoute = 'verify_notice'
    ) {}
}
