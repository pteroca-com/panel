<?php

namespace App\Core\Controller\API\Admin;

use App\Core\Service\Template\TemplateService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TemplateController extends AbstractAdminAPIController
{
    #[Route('/panel/api/template/{templateName}', name: 'api_get_template_info', methods: ['GET'])]
    public function checkVersion(
        string $templateName,
        TemplateService $templateService,
    ): JsonResponse
    {
        $this->grantAccess();

        return new JsonResponse($templateService->getTemplateInfo($templateName));
    }
}
