<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Entity\User;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Mailer\BoughtConfirmationEmailService;
use App\Core\Service\Pterodactyl\PterodactylService;
use Symfony\Component\Security\Core\User\UserInterface;

class RenewServerService extends AbstractActionServerService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly BoughtConfirmationEmailService $boughtConfirmationEmailService,
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

        $this->boughtConfirmationEmailService->sendRenewConfirmationEmail(
            $user,
            $server->getProduct(),
            $server,
            $this->getPterodactylAccountLogin($user),
        );
    }
}
