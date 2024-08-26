<?php

namespace App\Core\Service\System;

use Symfony\Component\HttpFoundation\RequestStack;

class IpAddressProviderService
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    public function getIpAddress(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp();
    }
}
