<?php

namespace App\Core\Controller;

use App\Core\Event\User\Authentication\UserLoginRequestedEvent;
use App\Core\Event\View\ViewDataEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthorizationController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        EventDispatcherInterface $eventDispatcher,
        Request $request,
    ): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('panel');
         }

        // Emit UserLoginRequestedEvent tylko dla niezalogowanych użytkowników
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
            'referer' => $request->headers->get('referer'),
        ];
        
        $loginRequestedEvent = new UserLoginRequestedEvent($context);
        $eventDispatcher->dispatch($loginRequestedEvent);

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Przygotuj dane widoku
        $viewData = [
            'error' => $error,
            'last_username' => $lastUsername,
            'csrf_token_intention' => 'authenticate',
            'target_path' => $this->generateUrl('panel'),
            'username_parameter' => 'email',
            'password_parameter' => 'password',
            'forgot_password_enabled' => true,
            'forgot_password_path' => $this->generateUrl('app_forgot_password_request'),
            'remember_me_enabled' => true,
            'remember_me_parameter' => 'custom_remember_me_param',
            'remember_me_checked' => true,
        ];
        
        // Emit ViewDataEvent dla pluginów
        $viewEvent = new ViewDataEvent('login', $viewData, null, $context);
        $eventDispatcher->dispatch($viewEvent);

        return $this->render('panel/login/login.html.twig', 
            $viewEvent->getViewData()
        );
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void {}
}
