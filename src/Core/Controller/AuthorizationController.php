<?php

namespace App\Core\Controller;

use App\Core\Event\User\Authentication\UserLoginRequestedEvent;
use App\Core\Event\View\ViewDataEvent;
use App\Core\Form\LoginFormType;
use App\Core\Trait\EventContextTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthorizationController extends AbstractController
{
    use EventContextTrait;

    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        Request $request,
    ): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('panel');
         }

        // Buduj context dla eventów
        $context = $this->buildEventContext($request);

        // Emit UserLoginRequestedEvent
        $this->dispatchEvent(new UserLoginRequestedEvent($context));

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
        $viewEvent = $this->dispatchEvent(new ViewDataEvent('login', $viewData, null, $context));

        return $this->render('panel/login/login.html.twig',
            $viewEvent->getViewData()
        );
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void {}
}
