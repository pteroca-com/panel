<?php

namespace App\Core\Controller;

use App\Core\Service\Authorization\SSOLoginRedirectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class SSOLoginRedirectController extends AbstractController
{
    #[Route('/sso/redirect', name: 'sso_redirect')]
    public function index(
        SSOLoginRedirectService $ssoLoginRedirectService,
        Request $request,
    ): Response {
        return $this->render('sso/redirect.html.twig', [
            'redirectToken' => $ssoLoginRedirectService->createSSOToken($this->getUser()),
            'redirectUrl' => $ssoLoginRedirectService->getPterodactylLoginUrl(),
            'redirectPath' => $this->getRedirectPath($request),
        ]);
    }

    private function getRedirectPath(Request $request): ?string
    {
        return $request->query->get('redirect_path')
            ?? $request->query->all()['routeParams']['redirect_path']
            ?? '/';
    }
}
