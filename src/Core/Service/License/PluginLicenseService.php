<?php

namespace App\Core\Service\License;

use App\Core\DTO\LicenseCheckResultDTO;

class PluginLicenseService extends AbstractLicenseService
{
    public function check(string $code, ?string $licenseKey, ?string $hash): LicenseCheckResultDTO
    {
        return $this->callVerifyApi($code, $licenseKey, $hash);
    }

    public function checkHashOnly(string $hash): LicenseCheckResultDTO
    {
        return $this->callHashOnlyApi($hash);
    }
}
