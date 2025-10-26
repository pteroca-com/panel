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

    /**
     * Generate a new purchase token for the user
     *
     * @param UserInterface $user
     * @param string $type Type of token: 'buy' or 'renew'
     * @return string The generated token
     */
    public function generateToken(UserInterface $user, string $type): string
    {
        // Clean up any existing tokens for this user and type to prevent token accumulation
        $this->purchaseTokenRepository->deleteUserTokensByType($user, $type);

        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32)); // 64 characters hex string

        $purchaseToken = new PurchaseToken();
        $purchaseToken->setToken($token);
        $purchaseToken->setUser($user);
        $purchaseToken->setType($type);

        $this->purchaseTokenRepository->save($purchaseToken);

        return $token;
    }

    /**
     * Validate and consume a purchase token (one-time use)
     *
     * @param string $token The token to validate
     * @param UserInterface $user The user making the purchase
     * @param string $type Type of token: 'buy' or 'renew'
     * @throws BadRequestHttpException If token is invalid or expired
     */
    public function validateAndConsumeToken(string $token, UserInterface $user, string $type): void
    {
        if (empty($token)) {
            throw new BadRequestHttpException('Purchase token is required.');
        }

        $purchaseToken = $this->purchaseTokenRepository->findValidToken($token, $user, $type);

        if ($purchaseToken === null) {
            throw new BadRequestHttpException('Invalid or already used purchase token. Please refresh the page and try again.');
        }

        // Check if token is expired (TTL: 1 hour)
        if ($purchaseToken->isExpired(self::TOKEN_TTL_SECONDS)) {
            $this->purchaseTokenRepository->deleteToken($purchaseToken);
            throw new BadRequestHttpException('Purchase token has expired. Please refresh the page and try again.');
        }

        // Token is valid - consume it (delete to prevent reuse)
        $this->purchaseTokenRepository->deleteToken($purchaseToken);
    }

    /**
     * Clean up expired tokens (for use in scheduled command)
     *
     * @return int Number of deleted tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $expiredBefore = new \DateTime("-" . self::TOKEN_TTL_SECONDS . " seconds");
        return $this->purchaseTokenRepository->deleteExpiredTokens($expiredBefore);
    }
}
