<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Enum\SettingEnum;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\SettingService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RenewServerService extends AbstractActionServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly SettingService $settingService,
        private readonly ServerService $serverService,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
        readonly UserRepository $userRepository,
    ) {
        parent::__construct($userRepository, $pterodactylService);
    }

    public function renewServer(Server $server, User|UserInterface $user): void
    {
        $currentExpirationDate = $server->getExpiresAt();
        if ($currentExpirationDate < new \DateTime()) {
            $currentExpirationDate = new \DateTime();
        } else {
            $currentExpirationDate = clone $currentExpirationDate;
        }
        $server->setExpiresAt($currentExpirationDate->modify('+1 month'));
        if ($server->getIsSuspended()) {
            $this->pterodactylService->getApi()->servers->unsuspend($server->getPterodactylServerId());
            $server->setIsSuspended(false);
        }
        $this->serverRepository->save($server);
        $this->updateUserBalance($user, $server->getProduct()->getPrice());
        $this->sendRenewConfirmationEmail($user, $server->getProduct(), $server);
    }

    private function sendRenewConfirmationEmail(User $user, Product $product, Server $server): void
    {
        $serverDetails = $this->serverService->getServerDetails($server);
        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.renew.subject'),
            'email/renew_product.html.twig',
            [
                'user' => $user,
                'product' => $product,
                'currency' => $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value),
                'server' => [
                    'ip' => $serverDetails['ip'],
                    'expiresAt' => $server->getExpiresAt()->format('Y-m-d H:i'),
                ],
                'panel' => [
                    'url' => $this->settingService->getSetting(SettingEnum::PTERODACTYL_PANEL_URL->value),
                    'username' => $this->getPterodactylAccountLogin($user),
                ],
            ]
        );
        $this->messageBus->dispatch($emailMessage);
    }
}