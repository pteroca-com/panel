<?php

namespace App\Core\Handler;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Entity\ServerProduct;
use App\Core\Entity\ServerProductPrice;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\ProductPriceUnitEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Repository\ServerProductPriceRepository;
use App\Core\Repository\ServerProductRepository;
use App\Core\Repository\ServerRepository;
use App\Core\Repository\UserRepository;
use App\Core\Service\Pterodactyl\PterodactylService;
use App\Core\Service\Server\ServerEggService;
use App\Core\Service\SettingService;
use Symfony\Component\Console\Style\SymfonyStyle;
use Timdesm\PterodactylPhpApi\PterodactylApi;
use Timdesm\PterodactylPhpApi\Resources\User as PterodactylUser;

class MigrateServersHandler implements HandlerInterface
{
    private int $limit = 100;

    private SymfonyStyle $io;

    private PterodactylApi $pterodactylApi;

    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly ServerRepository $serverRepository,
        private readonly ServerProductRepository $serverProductRepository,
        private readonly ServerProductPriceRepository $serverProductPriceRepository,
        private readonly UserRepository $userRepository,
        private readonly SettingService $settingService,
        private readonly ServerEggService $serverEggService,
    )
    {
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setIo(SymfonyStyle $io): self
    {
        $this->io = $io;

        return $this;
    }

    public function handle(bool $dryRun = false): void
    {
        $this->io->title('Pterodactyl Server Migration');
        $this->pterodactylApi = $this->pterodactylService->getApi();

        if ($dryRun) {
            $this->io->info('Running in dry-run mode - no changes will be made');
        }

        $pterodactylServers = $this->getPterodactylServers();
        $pterodactylUsers = $this->getPterodactylUsers();
        $pterocaServers = $this->getPterocaServers();
        $pterocaUsers = $this->getPterocaUsers();

        foreach ($pterodactylServers as $pterodactylServer) {
            if ($this->isServerAlreadyExists($pterodactylServer->toArray(), $pterocaServers)) {
                $infoMessage = sprintf(
                    'Server %s #%s already exists in PteroCA, skipping...',
                    $pterodactylServer['name'],
                    $pterodactylServer['identifier'],
                );
                $this->io->info($infoMessage);
                continue;
            }

            $pterodactylServerOwner = current(array_filter(
                $pterodactylUsers,
                fn(PterodactylUser $user) => $user->get('id') === $pterodactylServer['user']
            ));
            if (!in_array($pterodactylServerOwner->get('email'), array_column($pterocaUsers, 'email'))) {
                $warningMessage = sprintf(
                    'User %s does not exist in PteroCA, skipping server %s #%s...',
                    $pterodactylServerOwner->get('email'),
                    $pterodactylServer['name'],
                    $pterodactylServer['identifier'],
                );
                $this->io->warning($warningMessage);
                continue;
            }

            if ($this->isUserWantMigrateServer($pterodactylServer->toArray()) === false) {
                $this->io->info(sprintf(
                    'Skipping server %s #%s...',
                    $pterodactylServer['name'],
                    $pterodactylServer['identifier'],
                ));
                continue;
            }

            $this->io->section(sprintf(
                'Migrating server %s #%s...',
                $pterodactylServer['name'],
                $pterodactylServer['identifier'],
            ));;
            $this->io->info('You need to set the base price and duration for the server in PteroCA.');
            $duration = $this->askForDuration();
            $price = $this->askForPrice();

            if (!$dryRun) {
                $this->migrateServer(
                    $pterodactylServer->toArray(),
                    $pterodactylServerOwner->get('email'),
                    $duration,
                    $price
                );
            } else {
                $this->io->info('Dry run: Would migrate server but not saving changes');
            }
        }

        $this->io->success('Server migration completed.');
    }

    private function migrateServer(
        array $pterodactylServer,
        string $pterodactylServerOwnerEmail,
        int $duration,
        float $price,
    ): void
    {
        $pterocaUser = $this->userRepository->findOneBy(['email' => $pterodactylServerOwnerEmail]);
        if (empty($pterocaUser)) {
            $this->io->error(sprintf(
                'Could not find user with email %s, skipping server %s #%s...',
                $pterodactylServerOwnerEmail,
                $pterodactylServer['name'],
                $pterodactylServer['identifier'],
            ));
            return;
        }

        $serverEntity = $this->migrateServerEntity($pterocaUser, $pterodactylServer, $duration);
        $serverProductEntity = $this->migrateServerProductEntity($serverEntity, $pterodactylServer);
        $serverProductPriceEntity = $this->migrateServerProductPriceEntity($serverProductEntity, $duration, $price);
    }

    private function migrateServerProductPriceEntity(
        ServerProduct $serverProductEntity,
        int $duration,
        float $price
    ): ServerProductPrice
    {
        $serverProductPriceEntity = (new ServerProductPrice())
            ->setServerProduct($serverProductEntity)
            ->setType(ProductPriceTypeEnum::STATIC)
            ->setValue($duration)
            ->setUnit(ProductPriceUnitEnum::DAYS)
            ->setPrice($price)
            ->setIsSelected(true);
        $this->serverProductPriceRepository->save($serverProductPriceEntity);

        return $serverProductPriceEntity;
    }

    private function migrateServerProductEntity(Server $serverEntity, array $pterodactylServer): ServerProduct
    {
        $serverEggsConfiguration = $this->serverEggService
            ->prepareEggsConfiguration($pterodactylServer['id']);

        $serverProductEntity = (new ServerProduct())
            ->setServer($serverEntity)
            ->setOriginalProduct(null)
            ->setName(sprintf('%s #%s', $pterodactylServer['name'], $pterodactylServer['identifier']))
            ->setDiskSpace($pterodactylServer['limits']['disk'])
            ->setMemory($pterodactylServer['limits']['memory'])
            ->setIo($pterodactylServer['limits']['io'])
            ->setCpu($pterodactylServer['limits']['cpu'])
            ->setSwap($pterodactylServer['limits']['swap'])
            ->setBackups($pterodactylServer['feature_limits']['backups'])
            ->setPorts($pterodactylServer['feature_limits']['allocations'])
            ->setDbCount($pterodactylServer['feature_limits']['databases'])
            ->setNodes([$pterodactylServer['node']])
            ->setNest($pterodactylServer['nest'])
            ->setEggs([$pterodactylServer['egg']])
            ->setEggsConfiguration($serverEggsConfiguration)
            ->setAllowChangeEgg(false);
        $this->serverProductRepository->save($serverProductEntity);

        return $serverProductEntity;
    }

    private function migrateServerEntity(UserInterface $serverOwner, array $pterodactylServer, int $duration): Server
    {
        $expireAt = (new \DateTime())
            ->modify(sprintf('+%d days', $duration));

        $serverEntity = (new Server())
            ->setUser($serverOwner)
            ->setPterodactylServerId($pterodactylServer['id'])
            ->setPterodactylServerIdentifier($pterodactylServer['identifier'])
            ->setExpiresAt($expireAt)
            ->setIsSuspended($pterodactylServer['suspended'])
            ->setAutoRenewal(false);
        $this->serverRepository->save($serverEntity);

        return $serverEntity;
    }

    private function askForPrice(): float
    {
        $internalCurrency = $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value);
        $price = $this->io->ask(sprintf(
            'How much should the server cost? (%s)',
            $internalCurrency,
        ));
        if (!is_numeric($price) || $price <= 0) {
            $this->io->error('Price must be a positive number.');
            return $this->askForPrice();
        }

        return (float)$price;
    }

    private function askForDuration(): int
    {
        $duration = $this->io->ask('How long should the server be active? (in days)', 30);
        if (!is_numeric($duration) || $duration <= 0) {
            $this->io->error('Duration must be a positive number.');
            return $this->askForDuration();
        }

        return (int)$duration;
    }

    private function isUserWantMigrateServer(array $pterodactylServer): bool
    {
        $questionMessage = sprintf(
            'Do you want to migrate server %s #%s?',
            $pterodactylServer['name'],
            $pterodactylServer['identifier'],
        );

        return strtolower($this->io->ask($questionMessage, 'yes')) === 'yes';
    }

    private function isServerAlreadyExists(array $pterodactylServer, array $pterocaServers): bool
    {
        return in_array(
            $pterodactylServer['identifier'],
            array_column($pterocaServers, 'pterodactylServerIdentifier'),
        );
    }

    private function getPterodactylServers(): array
    {
        $this->io->section('Fetching servers from Pterodactyl...');
        $servers = $this->pterodactylApi->servers->all([
            'per_page' => $this->limit,
        ]);
        $this->io->info(sprintf('Fetched %d servers from Pterodactyl', count($servers->toArray())));

        return $servers->toArray();
    }

    private function getPterodactylUsers(): array
    {
        $this->io->section('Fetching users from Pterodactyl...');
        $users = $this->pterodactylApi->users->all([
            'per_page' => $this->limit,
        ]);
        $this->io->info(sprintf('Fetched %d users from Pterodactyl', count($users->toArray())));

        return $users->toArray();
    }

    private function getPterocaServers(): array
    {
        return array_map(
            fn(Server $server) => [
                'pterodactylServerIdentifier' => $server->getPterodactylServerIdentifier(),
            ],
            $this->serverRepository->findAll(),
        );
    }

    private function getPterocaUsers(): array
    {
        return array_map(
            fn(UserInterface $user) => [
                'email' => $user->getEmail(),
            ],
            $this->userRepository->findAll(),
        );
    }
}
