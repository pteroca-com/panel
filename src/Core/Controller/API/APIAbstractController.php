<?php

namespace App\Core\Controller\API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


abstract class APIAbstractController extends AbstractController
{
    public function requireAdminRoleForAPIEndpoint(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }
}
