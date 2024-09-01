<?php

namespace App\Core\Tests\Unit\Service\System;

use App\Core\Service\System\EnvironmentConfigurationService;
use PHPUnit\Framework\TestCase;

class EnvironmentConfigurationServiceTest extends TestCase
{
    public function testWriteToEnvFileWithExistingPattern(): void
    {
        $service = $this->getMockBuilder(EnvironmentConfigurationService::class)
            ->onlyMethods(['fileExists', 'fileGetContents', 'filePutContents'])
            ->getMock();

        $service->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $service->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('APP_ENV=dev');

        $service->expects($this->once())
            ->method('filePutContents')
            ->with($this->anything(), "APP_ENV=prod");

        $result = $service->writeToEnvFile('/^APP_ENV=.*/m', 'APP_ENV=prod');
        $this->assertTrue($result);
    }

    public function testWriteToEnvFileWithNewValue(): void
    {
        $service = $this->getMockBuilder(EnvironmentConfigurationService::class)
            ->onlyMethods(['fileExists', 'fileGetContents', 'filePutContents'])
            ->getMock();

        $service->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $service->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('EXISTING_VAR=value');

        $expectedContent = "EXISTING_VAR=value" . PHP_EOL . "NEW_VAR=new_value" . PHP_EOL;

        $service->expects($this->once())
            ->method('filePutContents')
            ->with($this->anything(), $expectedContent);

        $result = $service->writeToEnvFile('/^NEW_VAR=.*/m', 'NEW_VAR=new_value');
        $this->assertTrue($result);
    }

    public function testGetEnvValue(): void
    {
        $service = $this->getMockBuilder(EnvironmentConfigurationService::class)
            ->onlyMethods(['fileExists', 'fileGetContents'])
            ->getMock();

        $service->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $service->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('APP_ENV=prod');

        $result = $service->getEnvValue('/^APP_ENV=(.*)$/m');
        $this->assertEquals('prod', $result);
    }

    public function testGetEnvValueWhenPatternDoesNotMatch(): void
    {
        $service = $this->getMockBuilder(EnvironmentConfigurationService::class)
            ->onlyMethods(['fileExists', 'fileGetContents'])
            ->getMock();

        $service->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $service->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('APP_ENV=prod');

        $result = $service->getEnvValue('/^NON_EXISTENT_VAR=(.*)$/m');
        $this->assertEquals('', $result);
    }

    public function testWriteToEnvFileWhenFileDoesNotExist(): void
    {
        $service = $this->getMockBuilder(EnvironmentConfigurationService::class)
            ->onlyMethods(['fileExists'])
            ->getMock();

        $service->expects($this->once())
            ->method('fileExists')
            ->willReturn(false);

        $result = $service->writeToEnvFile('/^APP_ENV=.*/m', 'APP_ENV=prod');
        $this->assertFalse($result);
    }
}
