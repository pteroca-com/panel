<?php

namespace App\Core\Service\Captcha\Provider;

interface CaptchaProviderInterface
{
    public function validateCaptcha(string $captchaResponse): bool;
}