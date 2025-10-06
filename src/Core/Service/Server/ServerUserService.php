<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Entity\ServerSubuser;
use App\Core\Contract\UserInterface;
use App\Core\Service\SettingService;
use App\Core\Enum\ServerLogActionEnum;
use App\Core\Repository\UserRepository;
use App\Core\Service\Logs\ServerLogService;
use App\Core\Enum\EmailVerificationValueEnum;
use App\Core\Repository\ServerSubuserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;

class ServerUserService
{
    public function __construct(
        private readonly PterodactylApplicationService $pterodactylApplicationService,
        private readonly ServerLogService $serverLogService,
        private readonly ServerSubuserRepository $serverSubuserRepository,
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly SettingService $settingService,
    ) {}

    public function getAllSubusers(Server $server, UserInterface $user): array
    {
        return $this->pterodactylApplicationService
            ->getClientApi($user)
            ->users()
            ->getUsers($server->getPterodactylServerIdentifier())
            ->toArray();
    }

    public function addExistingUserToServer(
        Server $server,
        UserInterface $user,
        string $email,
        array $permissions = []
    ): array
    {
        $pterodactylClientApi = $this->pterodactylApplicationService
            ->getClientApi($user);

        try {
            $existingPterodactylUsers = $this->pterodactylApplicationService
                ->getApplicationApi()
                ->users()
                ->getAllUsers(['filter[email]' => $email]);
            $existingPterocaUser = $this->userRepository->findOneBy(['email' => $email]);

            if (count($existingPterodactylUsers->toArray()) === 0 || !$existingPterocaUser) {
                throw new \Exception($this->translator->trans('pteroca.api.server_user.user_not_exist', ['email' => $email]));
            }

            $verificationSetting = $this->settingService->getSetting(SettingEnum::REQUIRE_EMAIL_VERIFICATION->value);
            if ($verificationSetting === EmailVerificationValueEnum::REQUIRED->value && !$existingPterocaUser->isVerified()) {
                throw new \Exception($this->translator->trans('pteroca.api.server_user.user_not_verified', ['email' => $email]));
            }

            $currentSubusers = $this->getAllSubusers($server, $user);
            foreach ($currentSubusers['data'] ?? [] as $subuser) {
                if (isset($subuser['attributes']['email']) && $subuser['attributes']['email'] === $email) {
                    throw new \Exception($this->translator->trans('pteroca.api.server_user.user_already_added', ['email' => $email]));
                }
            }

            $result = $pterodactylClientApi->users()
                ->createUser($server->getPterodactylServerIdentifier(), $email, $permissions);

            $this->syncServerSubuser($server, $existingPterocaUser, $permissions);

            $this->serverLogService->logServerAction(
                $user,
                $server,
                ServerLogActionEnum::CREATE_SUBUSER,
                [
                    'email' => $email,
                    'permissions_count' => count($permissions),
                ]
            );

            return $result->toArray();

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'No user found') ||
                str_contains($e->getMessage(), 'does not exist')) {
                throw new \Exception($this->translator->trans('pteroca.api.server_user.user_not_exist', ['email' => $email]));
            }
            
            if (str_contains($e->getMessage(), 'already assigned') ||
                str_contains($e->getMessage(), 'already exists')) {
                throw new \Exception($this->translator->trans('pteroca.api.server_user.user_already_added', ['email' => $email]));
            }

            throw $e;
        }
    }

    public function updateSubuserPermissions(
        Server $server,
        UserInterface $user,
        string $subuserUuid,
        string $email,
        array $permissions
    ): array
    {
        $existingPterocaUser = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$existingPterocaUser) {
            throw new \Exception($this->translator->trans('pteroca.api.server_user.user_not_exist', ['email' => $email]));
        }

        $this->validateSubuserModification($server, $user, $email, $this->translator->trans('pteroca.api.server_user.modify_permissions'));

        $result = $this->pterodactylApplicationService
            ->getClientApi($user)
            ->users()
            ->updateUserPermissions($server->getPterodactylServerIdentifier(), $subuserUuid, $permissions);

        $this->syncServerSubuser($server, $existingPterocaUser, $permissions);

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::UPDATE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
                'subuser_email' => $email,
                'permissions_count' => count($permissions),
            ]
        );

        return $result->toArray();
    }

    public function deleteSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid,
        string $email
    ): void
    {
        $existingPterocaUser = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$existingPterocaUser) {
            throw new \Exception($this->translator->trans('pteroca.api.server_user.user_not_exist', ['email' => $email]));
        }

        $this->validateSubuserModification($server, $user, $email, $this->translator->trans('pteroca.api.server_user.delete_yourself_from_server'));

        $this->pterodactylApplicationService
            ->getClientApi($user)
            ->users()
            ->deleteUser($server->getPterodactylServerIdentifier(), $subuserUuid);

        $existingSubuser = $this->serverSubuserRepository->findSubuserByServerAndUser($server, $existingPterocaUser);
        if ($existingSubuser) {
            $this->serverSubuserRepository->delete($existingSubuser);
        }

        $this->serverLogService->logServerAction(
            $user,
            $server,
            ServerLogActionEnum::DELETE_SUBUSER,
            [
                'subuser_uuid' => $subuserUuid,
                'subuser_email' => $email,
            ]
        );
    }

    public function getSubuser(
        Server $server,
        UserInterface $user,
        string $subuserUuid
    ): array
    {
        return $this->pterodactylApplicationService
            ->getClientApi($user)
            ->users()
            ->getUser($server->getPterodactylServerIdentifier(), $subuserUuid)
            ->toArray();
    }

    private function syncServerSubuser(Server $server, UserInterface $subuserEntity, array $permissions): void
    {
        $existingSubuser = $this->serverSubuserRepository->findSubuserByServerAndUser($server, $subuserEntity);
        
        if ($existingSubuser) {
            $existingSubuser->setPermissions($permissions);
            $this->serverSubuserRepository->save($existingSubuser);
        } else {
            $serverSubuser = new ServerSubuser();
            $serverSubuser->setServer($server);
            $serverSubuser->setUser($subuserEntity);
            $serverSubuser->setPermissions($permissions);
            $this->serverSubuserRepository->save($serverSubuser);
        }
    }

    private function validateSubuserModification(Server $server, UserInterface $user, string $targetEmail, string $action): void
    {
        $isServerOwner = $server->getUser()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        
        if (!$isServerOwner && !$isAdmin && $user->getEmail() === $targetEmail) {
            throw new \Exception($this->translator->trans('pteroca.api.server_user.cannot_modify_yourself', ['action' => $action]));
        }
    }
}
