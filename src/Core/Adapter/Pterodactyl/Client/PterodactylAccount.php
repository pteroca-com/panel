<?php

namespace App\Core\Adapter\Pterodactyl\Client;

use App\Core\Contract\Pterodactyl\Client\PterodactylAccountInterface;
use App\Core\DTO\Pterodactyl\Client\PterodactylAccount as PterodactylAccountDto;
use App\Core\DTO\Pterodactyl\Client\PterodactylApiKey;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylAccount extends AbstractPterodactylClientAdapter implements PterodactylAccountInterface
{
    public function getAccount(): PterodactylAccountDto
    {
        $response = $this->makeRequest('GET', 'account');
        $data = $this->validateClientResponse($response, 200);
        
        return new PterodactylAccountDto($this->getDataFromResponse($data), $this->getMetaFromResponse($data));
    }

    public function updateAccount(array $details): bool
    {
        $response = $this->makeRequest('PUT', 'account', ['json' => $details]);
        return in_array($response->getStatusCode(), [200, 201, 204]);
    }

    public function updateEmail(string $email, string $currentPassword): bool
    {
        $response = $this->makeRequest('PUT', 'account/email', [
            'json' => [
                'email' => $email,
                'password' => $currentPassword
            ]
        ]);
        return in_array($response->getStatusCode(), [200, 201, 204]);
    }

    public function updatePassword(string $currentPassword, string $newPassword, string $passwordConfirmation): bool
    {
        $response = $this->makeRequest('PUT', 'account/password', [
            'json' => [
                'current_password' => $currentPassword,
                'password' => $newPassword,
                'password_confirmation' => $passwordConfirmation
            ]
        ]);
        return in_array($response->getStatusCode(), [200, 201, 204]);
    }

    public function enableTwoFactor(string $code): bool
    {
        $response = $this->makeRequest('POST', 'account/two-factor', [
            'json' => ['code' => $code]
        ]);
        return $response->getStatusCode() === 200;
    }

    public function disableTwoFactor(string $password): bool
    {
        $response = $this->makeRequest('POST', 'account/two-factor/disable', [
            'json' => ['password' => $password]
        ]);
        return $response->getStatusCode() === 204;
    }

    public function getTwoFactorQrCode(): string
    {
        $response = $this->makeRequest('GET', 'account/two-factor/qr');
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to retrieve QR code');
        }

        return $response->getContent();
    }

    public function getApiKeys(): Collection
    {
        $response = $this->makeRequest('GET', 'account/api-keys');
        $data = $this->validateListResponse($response, 200);

        $items = [];
        foreach ($data['data'] as $keyData) {
            $items[] = new PterodactylApiKey($keyData['attributes']);
        }

        return new Collection($items, $this->getMetaFromResponse($data));
    }

    public function createApiKey(string $description, array $allowedIps = []): PterodactylApiKey
    {
        $payload = ['description' => $description];
        if (!empty($allowedIps)) {
            $payload['allowed_ips'] = $allowedIps;
        }

        $response = $this->makeRequest('POST', 'account/api-keys', ['json' => $payload]);
        $data = $this->validateClientResponse($response, 201);
        
        // Dla nowo utworzonego klucza API, dodajemy secret_token z meta
        $attributes = $data['attributes'];
        if (isset($data['meta']['secret_token'])) {
            $attributes['secret_token'] = $data['meta']['secret_token'];
        }

        return new PterodactylApiKey($attributes, $this->getMetaFromResponse($data));
    }

    public function deleteApiKey(string $identifier): bool
    {
        $response = $this->makeRequest('DELETE', "account/api-keys/{$identifier}");
        return $response->getStatusCode() === 204;
    }
}
