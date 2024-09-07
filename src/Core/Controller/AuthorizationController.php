<?php

namespace App\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthorizationController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
    ): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('panel');
         }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('panel/login/login.html.twig', [
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
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void {}
}
