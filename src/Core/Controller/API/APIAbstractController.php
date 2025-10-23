<?php

namespace App\Core\Controller\API;

use App\Core\Trait\GetUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class APIAbstractController extends AbstractController
{
    use GetUserTrait;

    public function requireAdminRoleForAPIEndpoint(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }
}
