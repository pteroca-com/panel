<?php

namespace App\Core\Tests\Unit\Service;

use App\Core\Service\LocaleService;
use PHPUnit\Framework\TestCase;

class LocaleServiceTest extends TestCase
{
    public function testGetAvailableLocales(): void
    {
        $localeService = new LocaleService();

        $result = $localeService->getAvailableLocales();

        $this->assertNotEmpty($result);
    }
}
