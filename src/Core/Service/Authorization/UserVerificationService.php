<?php

namespace App\Core\Service\Authorization;

use App\Core\Enum\SettingEnum;
use App\Core\Contract\UserInterface;
use App\Core\Service\SettingService;
use App\Core\Enum\EmailVerificationValueEnum;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserVerificationService
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function validateUserVerification(UserInterface $user): void
    {
        $isVerificationRequiredValue = $this->settingService
            ->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);

        if ($isVerificationRequiredValue === EmailVerificationValueEnum::REQUIRED->value && !$user->isVerified()) {
            throw new \Exception($this->translator->trans('pteroca.system.email_not_verified'));
        }
    }
}
