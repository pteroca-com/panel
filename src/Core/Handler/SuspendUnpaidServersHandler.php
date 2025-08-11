<?php

namespace App\Core\Handler;

use App\Core\Entity\Server;
use App\Core\Message\SendEmailMessage;
use App\Core\Repository\ServerRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\RenewServerService;
use App\Core\Service\StoreService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class SuspendUnpaidServersHandler implements HandlerInterface
{

    public function __construct(
        private ServerRepository $serverRepository,
        private PterodactylService $pterodactylService,
        private StoreService $storeService,
        private RenewServerService $renewServerService,
        private TranslatorInterface $translator,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {}

    public function handle(): void
    {
       $this->handleServersToSuspend();
    }

    private function handleServersToSuspend(): void
    {
        $serversToSuspend = $this->serverRepository->getServersToSuspend(new \DateTime());
        foreach ($serversToSuspend as $server) {
            if ($this->tryToRenewServer($server)) {
                continue;
            }

//            $server->setAutoRenewal(false);
            $server->setIsSuspended(true);
            $this->serverRepository->save($server);

            $this->pterodactylService->getApi()
                ->servers
                ->suspend($server->getPterodactylServerId());

            // TODO finish this mail
            $emailMessage = new SendEmailMessage(
                $server->getUser()->getEmail(),
                $this->translator->trans('pteroca.email.suspended.subject'),
                'email/server_suspended.html.twig',
                ['user' => $server->getUser()],
            );
            $this->messageBus->dispatch($emailMessage);
        }
    }

    private function tryToRenewServer(Server $server): bool
    {
        if (!$server->isAutoRenewal()) {
            return false;
        }

        try {
            $this->storeService->validateUserBalanceByPrice(
                $server->getUser(),
                $server->getServerProduct()->getSelectedPrice()
            );
            $this->renewServerService->renewServer($server, $server->getUser());
        } catch (Exception $e) {
            $this->logger->warning('Failed to auto-renew server before suspension', [
                'server_id' => $server->getId(),
                'ptero_server_id' => $server->getPterodactylServerId(),
                'user_id' => $server->getUser()?->getId(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        return true;
    }
}
