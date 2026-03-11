<?php

namespace App\Core\DTO;

readonly class LicenseCheckResultDTO
{
    public function __construct(
        public bool $allowed = true,
        public bool $requiresLicense = false,
        public bool $fileBlacklisted = false,
        public ?string $blacklistReason = null,
        public ?bool $licenseValid = null,
        public ?string $error = null,
        public bool $apiUnavailable = false,
    ) {}

    public static function allowed(): self
    {
        return new self(allowed: true);
    }

    public static function apiUnavailable(): self
    {
        return new self(allowed: true, apiUnavailable: true);
    }

    public static function requiresLicense(): self
    {
        return new self(allowed: false, requiresLicense: true);
    }

    public static function fileBlacklisted(string $reason): self
    {
        return new self(allowed: false, fileBlacklisted: true, blacklistReason: $reason);
    }

    public static function licenseInvalid(string $error): self
    {
        return new self(allowed: false, requiresLicense: true, licenseValid: false, error: $error);
    }

    public static function licenseValid(): self
    {
        return new self(allowed: true, requiresLicense: true, licenseValid: true);
    }

}
