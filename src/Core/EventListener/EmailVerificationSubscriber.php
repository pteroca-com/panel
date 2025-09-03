<?php

namespace App\Core\EventListener;

use App\Core\Attribute\RequiresVerifiedEmail;
use App\Core\Enum\EmailVerificationValueEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Contract\UserInterface;
use App\Core\Service\SettingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly SettingService $settingService,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        
        if (!is_array($controller)) {
            return;
        }

        [$controllerObj, $method] = $controller;
        $request = $event->getRequest();

        $verificationMode = $this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
        if ($verificationMode !== EmailVerificationValueEnum::REQUIRED->value) {
            return;
        }

        $methodReflection = new \ReflectionMethod($controllerObj, $method);
        $methodAttributes = $methodReflection->getAttributes(RequiresVerifiedEmail::class);
        
        $classAttributes = [];
        if (empty($methodAttributes)) {
            $classReflection = new \ReflectionClass($controllerObj);
            $classAttributes = $classReflection->getAttributes(RequiresVerifiedEmail::class);
        }

        $attributes = array_merge($methodAttributes, $classAttributes);
        if (empty($attributes)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        if ($user->isVerified()) {
            return;
        }

        $attribute = $attributes[0]->newInstance();
        $redirectRoute = $attribute->redirectRoute;

        if ($request->isXmlHttpRequest()) {
            $event->setController(fn() => new JsonResponse([
                'error' => 'email_verification_required',
                'message' => 'Email verification is required to access this resource',
                'redirect_url' => $this->urlGenerator->generate($redirectRoute)
            ], 401));
            return;
        }

        $event->setController(fn() => new RedirectResponse(
            $this->urlGenerator->generate($redirectRoute)
        ));
    }
}
