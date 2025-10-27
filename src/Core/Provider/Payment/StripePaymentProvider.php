<?php

namespace App\Core\Provider\Payment;

use App\Core\Adapter\StripeAdapter;
use App\Core\DTO\PaymentSessionDTO;
use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Contracts\Translation\TranslatorInterface;

class StripePaymentProvider implements PaymentProviderInterface
{
    private bool $isConfigured = false;

    public function __construct(
        private readonly SettingService $settingService,
        private readonly TranslatorInterface $translator,
        private readonly StripeAdapter $stripeAdapter,
    ) {
    }

    private function setStripeApiKey(): void
    {
        $apiKey = $this->settingService
            ->getSetting(SettingEnum::STRIPE_SECRET_KEY->value);
        if (empty($apiKey)) {
            throw new \Exception($this->translator->trans('pteroca.recharge.payment_is_not_configured'));
        }
        $this->stripeAdapter->setApiKey($apiKey);
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
    ): ?PaymentSessionDTO
    {
        if (!$this->isConfigured) {
            $this->setStripeApiKey();
        }
        try {
            return $this->stripeAdapter->createSession([
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
        } catch (\Exception $e) {
            return null;
        }
    }

    public function retrieveSession(string $sessionId): ?PaymentSessionDTO
    {
        if (!$this->isConfigured) {
            $this->setStripeApiKey();
        }

        try {
            $stripeSession = $this->stripeAdapter->retrieveSession($sessionId);
        } catch (\Exception) {
            return null;
        }

        return $stripeSession;
    }

    public function getIdentifier(): string
    {
        return 'stripe';
    }

    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    public function getIcon(): string
    {
        return 'fab fa-stripe';
    }

    public function isConfigured(): bool
    {
        $apiKey = $this->settingService->getSetting(SettingEnum::STRIPE_SECRET_KEY->value);

        return !empty($apiKey);
    }

    public function getDescription(): string
    {
        return $this->translator->trans('pteroca.payment.gateway.stripe.description');
    }

    public function getSupportedCurrencies(): array
    {
        // Stripe supports 135+ currencies, listing most common ones
        return [
            'USD', 'EUR', 'GBP', 'PLN', 'CHF', 'CZK', 'DKK', 'SEK', 'NOK',
            'CAD', 'AUD', 'JPY', 'BGN', 'HRK', 'HUF', 'RON', 'ISK', 'NZD',
            'BRL', 'INR', 'MXN', 'SGD', 'HKD', 'ZAR', 'THB', 'MYR',
        ];
    }

    public function getMetadata(): array
    {
        return [
            'logo' => '/assets/img/payment/stripe-logo.svg',
            'color' => '#635bff',
            'supports_recurring' => true,
            'payment_methods' => $this->getStripePaymentMethods(),
        ];
    }
}
