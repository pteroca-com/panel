<?php

namespace App\Core\Controller\Panel;

use App\Core\Controller\AbstractController;
use App\Core\Enum\UserRoleEnum;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverviewController extends AbstractController
{
    #[Route('/admin/overview', name: 'admin_overview')]
    public function index(): Response
    {
        $this->checkPermission(UserRoleEnum::ROLE_ADMIN->name);

        return $this->render('panel/admin/overview.html.twig');
    }
}