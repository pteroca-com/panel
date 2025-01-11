<?php

namespace App\Core\Controller\API\Admin;

use App\Core\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AbstractAdminAPIController extends AbstractController
{
    public function grantAccess(): void
    {
        if (!$this->isGranted(UserRoleEnum::ROLE_ADMIN->name)) {
            throw new AccessDeniedHttpException();
        }
    }
}
