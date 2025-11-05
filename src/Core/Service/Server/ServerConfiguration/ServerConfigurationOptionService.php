<?php

namespace App\Core\Service\Server\ServerConfiguration;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Event\Server\Configuration\ServerStartupOptionUpdateRequestedEvent;
use App\Core\Event\Server\Configuration\ServerStartupOptionUpdatedEvent;
use App\Core\Service\Event\EventContextService;
use App\Core\Service\Pterodactyl\PterodactylApplicationService;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ServerConfigurationOptionService extends AbstractServerConfiguration
{
    public function __construct(
        private readonly PterodactylApplicationService    $pterodactylApplicationService,
        private readonly ServerConfigurationStartupService $serverConfigurationStartupService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly EventContextService $eventContextService,
    ) {
        parent::__construct($this->pterodactylApplicationService);
    }

    /**
     * @throws Exception
     */
    public function updateServerStartupOption(
        Server $server,
        UserInterface $user,
        string $variableKey,
        string $variableValue,
    ): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $this->eventContextService->buildMinimalContext($request) : [];

        $originalVariableKey = $variableKey;
        $variableKey = $this->mapVariableKey($variableKey);
        $serverDetails = $this->getServerDetails($server, ['egg']);

        $oldValue = match ($variableKey) {
            'startup' => $serverDetails['container']['startup_command'] ?? '',
            'image' => $serverDetails['container']['image'] ?? '',
            default => ''
        };

        $requestedEvent = new ServerStartupOptionUpdateRequestedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $originalVariableKey,
            $variableValue,
            $context
        );
        $this->eventDispatcher->dispatch($requestedEvent);

        if ($requestedEvent->isPropagationStopped()) {
            $reason = $requestedEvent->getRejectionReason() ?? 'Server startup option update was blocked';
            throw new Exception($reason);
        }

        $this->validateVariableValue($variableKey, $variableValue, $serverDetails);

        $startupPayload = $this->serverConfigurationStartupService
            ->getStartupPayload($variableKey, $variableValue, $serverDetails);

        $this->serverConfigurationStartupService
            ->updateServerStartup($server, $startupPayload);

        $updatedEvent = new ServerStartupOptionUpdatedEvent(
            $user->getId(),
            $server->getId(),
            $server->getPterodactylServerIdentifier(),
            $originalVariableKey,
            $variableValue,
            $oldValue,
            $context
        );
        $this->eventDispatcher->dispatch($updatedEvent);
    }

    /**
     * @throws Exception
     */
    private function mapVariableKey(string $variableKey): string
    {
        return match ($variableKey) {
            'startup' => 'startup',
            'docker_image' => 'image',
            default => throw new Exception('Invalid variable key'),
        };
    }

    /**
     * @throws Exception
     */
    private function validateVariableValue(string $variableKey, string $variableValue, array $serverDetails): void
    {
        if (empty($variableValue)) {
            throw new Exception('Invalid variable value');
        }

        if ($variableKey === 'image') {
            $availableImages = $serverDetails['relationships']['egg']->get('docker_images');
            $selectedImage = array_filter($availableImages, fn($image) => $image === $variableValue);
            if (empty($selectedImage)) {
                throw new Exception('Invalid image');
            }
        }
    }
}
