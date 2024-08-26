<?php

namespace App\Core\Service\Authorization;

use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UserVerificationService
{
    public function __construct(
        private SettingService $settingService,
        private TranslatorInterface $translator,
    ) {}

    public function validateUserVerification(User $user): void
    {
        $isVerificationRequired = $this->settingService
            ->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
        if ($isVerificationRequired && !$user->isVerified()) {
            throw new \Exception($this->translator->trans('pteroca.system.email_not_verified'));
        }
    }
}
