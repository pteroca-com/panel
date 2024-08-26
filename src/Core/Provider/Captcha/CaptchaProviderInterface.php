<?php

namespace App\Core\Provider\Captcha;

interface CaptchaProviderInterface
{
    public function validateCaptcha(string $captchaResponse): bool;
}