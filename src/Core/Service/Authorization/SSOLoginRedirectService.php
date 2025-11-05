<?php

namespace App\Core\Service\Authorization;

use App\Core\Contract\UserInterface;
use App\Core\Enum\SettingEnum;
use App\Core\Event\SSO\SSOFailedEvent;
use App\Core\Event\SSO\SSOTokenGeneratedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\SettingService;
use DateTimeImmutable;
use Exception;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class SSOLoginRedirectService
{
    public function __construct(
        private SettingService           $settingService,
        private EventDispatcherInterface $eventDispatcher,
        private EventContextService      $eventContextService,
        private RequestStack             $requestStack,
    ) {}

    /**
     * @throws Exception
     */
    public function createSSOToken(UserInterface $user): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $this->eventContextService->buildNullableContext($request);

        try {
            $pterodactylSsoSecret = $this->settingService->getSetting(SettingEnum::PTERODACTYL_SSO_SECRET->value);
            if (empty($pterodactylSsoSecret)) {
                throw new Exception('PTERODACTYL_SSO_SECRET is not set');
            }

            $targetUrl = $this->getPterodactylLoginUrl();
            $expirationTime = time() + 60;

            $payload = [
                'iss' => $this->settingService->getSetting(SettingEnum::SITE_URL->value),
                'aud' => $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value),
                'iat' => time(),
                'exp' => $expirationTime,
                'user' => [
                    'id' => $user->getPterodactylUserId(),
                ],
            ];

            $token = JWT::encode($payload, $pterodactylSsoSecret, 'HS256');
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTimeImmutable())->setTimestamp($expirationTime);

            // Emit SSOTokenGeneratedEvent (post)
            $this->eventDispatcher->dispatch(new SSOTokenGeneratedEvent(
                $user->getId(),
                $user->getPterodactylUserId(),
                $tokenHash,
                $expiresAt,
                $targetUrl,
                $context
            ));

            return $token;
        } catch (Exception $e) {
            // Emit SSOFailedEvent (error)
            $this->eventDispatcher->dispatch(new SSOFailedEvent(
                $user->getId(),
                $e->getMessage(),
                'token_generation',
                $context
            ));

            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function getPterodactylLoginUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $this->eventContextService->buildNullableContext($request);

        try {
            $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
            if (empty($pterodactylUrl)) {
                throw new Exception('PTERODACTYL_PANEL_URL is not set');
            }

            return sprintf('%s/pteroca/authorize', $pterodactylUrl);
        } catch (Exception $e) {
            // Emit SSOFailedEvent (error)
            $this->eventDispatcher->dispatch(new SSOFailedEvent(
                null,
                $e->getMessage(),
                'url_validation',
                $context
            ));

            throw $e;
        }
    }
}
