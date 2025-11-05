<?php

namespace App\Core\Service\Payment;

use App\Core\Provider\Payment\PaymentProviderInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    private array $providers = [];

    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    public function registerProvider(PaymentProviderInterface $provider): void
    {
        $identifier = $provider->getIdentifier();

        if (isset($this->providers[$identifier])) {
            throw new InvalidArgumentException(
                sprintf('Payment provider "%s" is already registered', $identifier)
            );
        }

        $this->providers[$identifier] = $provider;
    }

    public function getProvider(string $identifier): ?PaymentProviderInterface
    {
        return $this->providers[$identifier] ?? null;
    }

    public function getAvailableProviders(): array
    {
        return array_filter(
            $this->providers,
            fn(PaymentProviderInterface $provider) => $provider->isConfigured()
        );
    }

    public function getAllProviders(): array
    {
        return $this->providers;
    }

    public function hasProvider(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    public function getProvidersForCurrency(string $currency): array
    {
        return array_filter(
            $this->getAvailableProviders(),
            fn(PaymentProviderInterface $provider) => in_array($currency, $provider->getSupportedCurrencies(), true)
        );
    }

    public function getDefaultProvider(): ?PaymentProviderInterface
    {
        $available = $this->getAvailableProviders();
        return !empty($available) ? reset($available) : null;
    }

    public function count(bool $onlyAvailable = false): int
    {
        return count($onlyAvailable ? $this->getAvailableProviders() : $this->providers);
    }
}
