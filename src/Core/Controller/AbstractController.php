<?php

namespace App\Core\Controller;

use App\Core\Enum\UserRoleEnum;
use App\Core\Enum\ViewNameEnum;
use App\Core\Trait\EventContextTrait;
use App\Core\Trait\GetUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends SymfonyAbstractController
{
    use GetUserTrait;
    use EventContextTrait;

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

    protected function renderWithEvent(
        ViewNameEnum $viewName,
        string $template,
        array $viewData,
        Request $request
    ): Response
    {
        $viewEvent = $this->prepareViewDataEvent($viewName, $viewData, $request);

        return $this->render($template, $viewEvent->getViewData());
    }
}