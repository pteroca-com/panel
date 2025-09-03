<?php

namespace App\Core\Service\Email;

use App\Core\Contract\ProductInterface;
use App\Core\Contract\ProductPriceInterface;
use App\Core\Contract\UserInterface;
use App\Core\DTO\Email\EmailContextDTO;
use App\Core\DTO\Email\PurchaseEmailContextDTO;
use App\Core\DTO\Email\RenewalEmailContextDTO;
use App\Core\Entity\Server;
use App\Core\Enum\SettingEnum;
use App\Core\Service\Server\ServerService;
use App\Core\Service\SettingService;
use App\Core\Exception\Email\ServerDetailsNotAvailableException;

class EmailContextBuilderService
{
    public function __construct(
        private readonly ServerService $serverService,
        private readonly SettingService $settingService,
        private readonly ClientPanelUrlResolverService $panelUrlResolver,
    ) {}

    public function buildPurchaseContext(
        UserInterface $user,
        Server $server,
        ProductInterface $product,
        ProductPriceInterface $selectedPrice,
        string $pterodactylAccountUsername,
    ): PurchaseEmailContextDTO {
        $baseContext = $this->buildBaseContext($user, $server, $pterodactylAccountUsername);
        
        return new PurchaseEmailContextDTO(
            user: $user,
            currency: $baseContext->getCurrency(),
            serverData: $baseContext->getServerData(),
            panelData: $baseContext->getPanelData(),
            product: $product,
            selectedPrice: $selectedPrice,
        );
    }

    public function buildRenewalContext(
        UserInterface $user,
        Server $server,
        ProductInterface $product,
        ProductPriceInterface $selectedPrice,
        string $pterodactylAccountUsername,
    ): RenewalEmailContextDTO {
        $baseContext = $this->buildBaseContext($user, $server, $pterodactylAccountUsername);
        
        return new RenewalEmailContextDTO(
            user: $user,
            currency: $baseContext->getCurrency(),
            serverData: $baseContext->getServerData(),
            panelData: $baseContext->getPanelData(),
            product: $product,
            selectedPrice: $selectedPrice,
        );
    }

    private function buildBaseContext(
        UserInterface $user,
        Server $server,
        string $pterodactylAccountUsername,
    ): EmailContextDTO {
        $serverDetails = $this->serverService->getServerDetails($server);
        
        if ($serverDetails === null) {
            throw new ServerDetailsNotAvailableException(
                sprintf('Server details not available for server ID: %d', $server->getId())
            );
        }

        $currency = $this->settingService->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME->value);
        $panelUrl = $this->panelUrlResolver->resolve();

        $serverData = [
            'ip' => $serverDetails->ip,
            'expiresAt' => $server->getExpiresAt()->format('Y-m-d H:i'),
        ];

        $panelData = [
            'url' => $panelUrl,
            'username' => $pterodactylAccountUsername,
        ];

        return new EmailContextDTO(
            user: $user,
            currency: $currency,
            serverData: $serverData,
            panelData: $panelData,
        );
    }
}
