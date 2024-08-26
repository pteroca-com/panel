<?php

namespace App\Core\Tests\Unit\Provider\Payment;

use App\Core\Adapter\StripeAdapter;
use App\Core\DTO\PaymentSessionDTO;
use App\Core\Enum\SettingEnum;
use App\Core\Provider\Payment\StripePaymentProvider;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class StripePaymentProviderTest extends TestCase
{
    private StripeAdapter $stripeAdapter;
    private SettingService $settingService;
    private TranslatorInterface $translator;
    private StripePaymentProvider $stripePaymentProvider;

    protected function setUp(): void
    {
        $this->stripeAdapter = $this->createMock(StripeAdapter::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->stripePaymentProvider = new StripePaymentProvider(
            $this->settingService,
            $this->translator,
            $this->stripeAdapter
        );
    }

    public function testCreateSessionSuccess(): void
    {
        $amount = 100.0;
        $currency = 'USD';
        $successUrl = 'https://example.com/success';
        $cancelUrl = 'https://example.com/cancel';
        $apiKey = 'valid-api-key';
        $paymentMethods = ['card'];

        $paymentSession = new PaymentSessionDTO(
            'session_id',
            $amount * 100,
            $currency,
            'created',
            'https://example.com/checkout'
        );

        $this->settingService
            ->expects($this->exactly(2))
            ->method('getSetting')
            ->willReturnMap([
                [SettingEnum::STRIPE_SECRET_KEY->value, $apiKey],
                [SettingEnum::STRIPE_PAYMENT_METHODS->value, implode(',', $paymentMethods)]
            ]);

        $this->stripeAdapter
            ->expects($this->once())
            ->method('setApiKey')
            ->with($apiKey);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('pteroca.recharge.payment_title')
            ->willReturn('Payment Title');

        $this->stripeAdapter
            ->expects($this->once())
            ->method('createSession')
            ->with([
                'payment_method_types' => $paymentMethods,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($currency),
                            'product_data' => [
                                'name' => 'Payment Title',
                            ],
                            'unit_amount' => $amount * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ])
            ->willReturn($paymentSession);

        $result = $this->stripePaymentProvider->createSession($amount, $currency, $successUrl, $cancelUrl);

        $this->assertInstanceOf(PaymentSessionDTO::class, $result);
        $this->assertEquals('session_id', $result->getId());
        $this->assertEquals($amount * 100, $result->getAmountTotal());
        $this->assertEquals($currency, $result->getCurrency());
        $this->assertEquals('created', $result->getPaymentStatus());
        $this->assertEquals('https://example.com/checkout', $result->getUrl());
    }

    public function testRetrieveSessionSuccess(): void
    {
        $sessionId = 'session_id';
        $apiKey = 'valid-api-key';

        $paymentSession = new PaymentSessionDTO(
            $sessionId,
            10000,
            'usd',
            'created',
            'https://example.com/checkout'
        );

        $this->settingService
            ->expects($this->once())
            ->method('getSetting')
            ->with(SettingEnum::STRIPE_SECRET_KEY->value)
            ->willReturn($apiKey);

        $this->stripeAdapter
            ->expects($this->once())
            ->method('setApiKey')
            ->with($apiKey);

        $this->stripeAdapter
            ->expects($this->once())
            ->method('retrieveSession')
            ->with($sessionId)
            ->willReturn($paymentSession);

        $result = $this->stripePaymentProvider->retrieveSession($sessionId);

        $this->assertInstanceOf(PaymentSessionDTO::class, $result);
        $this->assertEquals($sessionId, $result->getId());
        $this->assertEquals(10000, $result->getAmountTotal());
        $this->assertEquals('usd', $result->getCurrency());
        $this->assertEquals('created', $result->getPaymentStatus());
        $this->assertEquals('https://example.com/checkout', $result->getUrl());
    }
}
