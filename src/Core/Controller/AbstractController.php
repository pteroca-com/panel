<?php

namespace App\Core\Controller;

use App\Core\Enum\UserRoleEnum;
use App\Core\Trait\GetUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

abstract class AbstractController extends SymfonyAbstractController
{
    use GetUserTrait;

    public function checkPermission(string $permission = UserRoleEnum::ROLE_USER->name): void
    {
        $user = $this->getUser();

        if (empty($user)) {
            $this->redirect($this->generateUrl('app_login'));
        }

        if (!$this->isGranted($permission) || $user->isBlocked()) {
            throw $this->createAccessDeniedException('Access denied');
        }
    }
}