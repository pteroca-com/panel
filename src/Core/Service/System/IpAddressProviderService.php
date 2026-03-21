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
        if ($request === null) {
            return null;
        }

        $cfIp = $request->headers->get('CF-Connecting-IP');
        if (!empty($cfIp) && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }

        return $request->getClientIp();
    }
}
