<?php

namespace App\Core\Service\System;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class IpAddressProviderService
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function getIpAddress(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp();
    }
}
