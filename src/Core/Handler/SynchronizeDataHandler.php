<?php

namespace App\Core\Handler;

use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylClientApiKeyService;

class SynchronizeDataHandler implements HandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PterodactylClientApiKeyService $pterodactylClientApiKeyService,
    )
    {
    }

    public function handle(): void
    {
        $this->synchronizeUserPterodactylKeys();
    }

    private function synchronizeUserPterodactylKeys(): void
    {
        $usersWithoutPterodactylKeys = $this->userRepository
            ->findBy(['pterodactylUserApiKey' => null]);

        foreach ($usersWithoutPterodactylKeys as $user) {
            $pterodactylClientApiKey = $this->pterodactylClientApiKeyService->createClientApiKey($user);
            $user->setPterodactylUserApiKey($pterodactylClientApiKey);
            $this->userRepository->save($user);
        }
    }
}
