<?php

namespace App\Core\Trait;

use App\Core\Controller\Panel\ServerProductCrudController;
use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Enum\SettingEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

trait ManageServerActionTrait
{
    private function getServerAction(string $action): Action
    {
        return Action::new(
            'serverEdit',
            $this->translator->trans(sprintf('pteroca.crud.server.server_%s', $action)),
        )->linkToUrl(
            fn (Server|ServerProduct $entity) => $this->generateUrl(
                'panel',
                [
                    'crudAction' => $action,
                    'crudControllerFqcn' => ServerProductCrudController::class,
                    'entityId' => $this->getEntityProductId($entity),
                ]
            )
        );
    }

    private function getManageServerAction(): Action
    {
        $manageServerAction = Action::new(
            'manageServer',
            $this->translator->trans('pteroca.crud.server.show_server_dashboard'),
        );

        $usePterodactyl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_USE_AS_CLIENT_PANEL->value);
        if (empty($usePterodactyl)) {
            $manageServerAction->linkToRoute(
                'server',
                fn (Server|ServerProduct $entity) => ['id' => $this->getEntityPterodactylServerIdentifier($entity)],
            );
        } else {
            if ($this->settingService->getSetting(SettingEnum::PTERODACTYL_SSO_ENABLED->value)) {
                $manageServerAction->linkToRoute(
                    'sso_redirect',
                    fn (Server|ServerProduct $entity) => [
                        'redirect_path' => sprintf('/server/%s', $this->getEntityPterodactylServerIdentifier($entity)),
                    ]
                );
            } else {
                $pterodactylUrl = $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value);
                $manageServerAction->linkToUrl($pterodactylUrl);
            }
            $manageServerAction->setHtmlAttributes(['target' => '_blank']);
        }

        return $manageServerAction;
    }

    private function getEntityPterodactylServerIdentifier(Server|ServerProduct $entity): string
    {
        if ($entity instanceof Server) {
            return $entity->getPterodactylServerIdentifier();
        }

        return $entity->getServer()->getPterodactylServerIdentifier();
    }

    private function getEntityProductId(Server|ServerProduct $entity): int
    {
        if ($entity instanceof Server) {
            return $entity->getServerProduct()->getId();
        }

        return $entity->getId();
    }
}
