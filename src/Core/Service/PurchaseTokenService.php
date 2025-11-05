<?php

namespace App\Core\Service;

use App\Core\Contract\UserInterface;
use App\Core\Entity\PurchaseToken;
use App\Core\Repository\PurchaseTokenRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PurchaseTokenService
{
    private const TOKEN_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly PurchaseTokenRepository $purchaseTokenRepository,
    ) {}

    public function generateToken(UserInterface $user, string $type): string
    {
        $this->purchaseTokenRepository->deleteUserTokensByType($user, $type);
        $token = bin2hex(random_bytes(32)); // 64 characters hex string

        $purchaseToken = new PurchaseToken();
        $purchaseToken->setToken($token);
        $purchaseToken->setUser($user);
        $purchaseToken->setType($type);

        $this->purchaseTokenRepository->save($purchaseToken);

        return $token;
    }

    public function validateAndConsumeToken(string $token, UserInterface $user, string $type): void
    {
        if (empty($token)) {
            throw new BadRequestHttpException('Purchase token is required.');
        }

        $purchaseToken = $this->purchaseTokenRepository->findValidToken($token, $user, $type);

        if ($purchaseToken === null) {
            throw new BadRequestHttpException('Invalid or already used purchase token. Please refresh the page and try again.');
        }

        if ($purchaseToken->isExpired(self::TOKEN_TTL_SECONDS)) {
            $this->purchaseTokenRepository->deleteToken($purchaseToken);
            throw new BadRequestHttpException('Purchase token has expired. Please refresh the page and try again.');
        }

        // Token is valid - consume it (delete to prevent reuse)
        $this->purchaseTokenRepository->deleteToken($purchaseToken);
    }

    public function cleanupExpiredTokens(): int
    {
        $expiredBefore = new \DateTime("-" . self::TOKEN_TTL_SECONDS . " seconds");
        return $this->purchaseTokenRepository->deleteExpiredTokens($expiredBefore);
    }
}
