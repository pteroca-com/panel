<?php

namespace App\Core\Tests\Unit\Service\System;

use App\Core\Service\System\IpAddressProviderService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class IpAddressProviderServiceTest extends TestCase
{
    public function testGetIpAddressWhenRequestExists(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('127.0.0.1');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $ipAddressProviderService = new IpAddressProviderService($requestStack);

        $ipAddress = $ipAddressProviderService->getIpAddress();

        $this->assertEquals('127.0.0.1', $ipAddress);
    }

    public function testGetIpAddressWhenNoRequestExists(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $ipAddressProviderService = new IpAddressProviderService($requestStack);

        $ipAddress = $ipAddressProviderService->getIpAddress();

        $this->assertNull($ipAddress);
    }
}
