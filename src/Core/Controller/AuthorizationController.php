<?php

namespace App\Core\Controller;

use App\Core\Event\User\Authentication\UserLoginRequestedEvent;
use App\Core\Event\View\ViewDataEvent;
use App\Core\Form\LoginFormType;
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

        // Context dla eventów
        $context = [
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'locale' => $request->getLocale(),
            'referer' => $request->headers->get('referer'),
        ];
        
        // Emit UserLoginRequestedEvent
        $loginRequestedEvent = new UserLoginRequestedEvent($context);
        $eventDispatcher->dispatch($loginRequestedEvent);

        // Utwórz formularz logowania
        $form = $this->createForm(LoginFormType::class);
        
        // Pobierz błędy i ostatnią nazwę użytkownika
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Ustaw domyślną wartość email jeśli jest
        if ($lastUsername) {
            $form->get('email')->setData($lastUsername);
        }

        // Przygotuj dane widoku
        $viewData = [
            'loginForm' => $form->createView(),
            'error' => $error,
            'last_username' => $lastUsername,
            'action' => $this->generateUrl('app_login'),
            'forgot_password_enabled' => true,
            'forgot_password_path' => $this->generateUrl('app_forgot_password_request'),
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
