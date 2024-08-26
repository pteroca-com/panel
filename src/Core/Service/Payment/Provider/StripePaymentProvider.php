<?php

namespace App\Core\Service\Payment\Provider;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Contracts\Translation\TranslatorInterface;

class StripePaymentProvider implements PaymentProviderInterface
{
    public const PAID_STATUS = 'paid';

    private bool $isConfigured = false;

    public function __construct(
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    private function setStripeApiKey(): void
    {
        $apiKey = $this->settingService
            ->getSetting(SettingEnum::STRIPE_SECRET_KEY->value);
        if (empty($apiKey)) {
            throw new \Exception($this->translator->trans('pteroca.recharge.payment_is_not_configured'));
        }
        Stripe::setApiKey($apiKey);
        $this->isConfigured = true;
    }

    private function getStripePaymentMethods(): array
    {
        $methods = $this->settingService->getSetting(SettingEnum::STRIPE_PAYMENT_METHODS->value);
        if (empty($methods)) {
            return [];
        }
        $methods = explode(',', $methods);
        return array_map('trim', $methods);
    }

    public function createSession(
        float $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
    ): Session
    {
        if (!$this->isConfigured) {
            $this->setStripeApiKey();
        }
        return Session::create([
            'payment_method_types' => $this->getStripePaymentMethods(),
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => [
                            'name' => $this->translator->trans('pteroca.recharge.payment_title'),
                        ],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    public function retrieveSession(string $sessionId): Session
    {
        if (!$this->isConfigured) {
            $this->setStripeApiKey();
        }
        return Session::retrieve($sessionId);
    }
}
