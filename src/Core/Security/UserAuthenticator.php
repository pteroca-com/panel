<?php

namespace App\Core\Security;

use App\Core\Repository\UserRepository;
use App\Core\Service\Captcha\CaptchaService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly HttpClientInterface   $httpClient,
        private readonly CaptchaService $captchaService,
        private readonly TranslatorInterface   $translator,
        private readonly UserRepository        $userRepository,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $recaptchaResponse = $request->request->getString('g-recaptcha-response');

        if ($this->captchaService->isCaptchaEnabled()
            && !$this->captchaService->validateCaptcha($recaptchaResponse)) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('pteroca.login.invalid_captcha'));
        }

        $user = $this->userRepository->findByEmailIncludingDeleted($email);
        if (!empty($user)) {
            if ($user->isDeleted()) {
                throw new CustomUserMessageAuthenticationException($this->translator->trans('pteroca.login.invalid_credentials'));
            }
            if ($user->isBlocked()) {
                throw new CustomUserMessageAuthenticationException($this->translator->trans('pteroca.login.user_blocked'));
            }
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        $disableCsrf = isset($_ENV['DISABLE_CSRF']) && $_ENV['DISABLE_CSRF'] === 'true';
        $badges = [
            new RememberMeBadge(),
        ];
        if (!$disableCsrf) {
            $badges[] = new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token'));
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            $badges,
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('panel'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
