<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use App\Core\Enum\PluginStateEnum;
use App\Core\Exception\Plugin\InvalidStateTransitionException;
use Psr\Log\LoggerInterface;

class PluginStateMachine
{
    private const ALLOWED_TRANSITIONS = [
        PluginStateEnum::DISCOVERED->value => [
            PluginStateEnum::REGISTERED->value,
            PluginStateEnum::FAULTED->value,
        ],
        PluginStateEnum::REGISTERED->value => [
            PluginStateEnum::ENABLED->value,
            PluginStateEnum::FAULTED->value,
            PluginStateEnum::UPDATE_PENDING->value,
        ],
        PluginStateEnum::ENABLED->value => [
            PluginStateEnum::DISABLED->value,
            PluginStateEnum::FAULTED->value,
            PluginStateEnum::UPDATE_PENDING->value,
        ],
        PluginStateEnum::DISABLED->value => [
            PluginStateEnum::ENABLED->value,
            PluginStateEnum::UPDATE_PENDING->value,
        ],
        PluginStateEnum::UPDATE_PENDING->value => [
            PluginStateEnum::ENABLED->value,
            PluginStateEnum::DISABLED->value,
            PluginStateEnum::FAULTED->value,
        ],
        PluginStateEnum::FAULTED->value => [
            PluginStateEnum::REGISTERED->value,
            PluginStateEnum::DISABLED->value,
        ],
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function canTransition(PluginStateEnum $from, PluginStateEnum $to): bool
    {
        // Same state is always allowed (no-op)
        if ($from === $to) {
            return true;
        }

        $allowedStates = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowedStates, true);
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function validateTransition(Plugin $plugin, PluginStateEnum $targetState): void
    {
        $currentState = $plugin->getState();

        if (!$this->canTransition($currentState, $targetState)) {
            throw new InvalidStateTransitionException(
                $plugin->getName(),
                $currentState,
                $targetState
            );
        }
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function transition(Plugin $plugin, PluginStateEnum $targetState): void
    {
        $currentState = $plugin->getState();

        // Validate transition
        $this->validateTransition($plugin, $targetState);

        // No-op if same state
        if ($currentState === $targetState) {
            return;
        }

        // Log transition
        $this->logger->info("Plugin state transition: {$plugin->getName()} $currentState->value -> $targetState->value");

        // Execute transition
        $plugin->setState($targetState);
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function transitionToRegistered(Plugin $plugin): void
    {
        $this->transition($plugin, PluginStateEnum::REGISTERED);
        $plugin->markAsRegistered();
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function transitionToEnabled(Plugin $plugin): void
    {
        $this->transition($plugin, PluginStateEnum::ENABLED);
        $plugin->markAsEnabled();
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function transitionToDisabled(Plugin $plugin): void
    {
        $this->transition($plugin, PluginStateEnum::DISABLED);
        $plugin->markAsDisabled();
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function transitionToFaulted(Plugin $plugin, string $reason): void
    {
        $this->transition($plugin, PluginStateEnum::FAULTED);
        $plugin->markAsFaulted($reason);
    }

    /**
     * @throws InvalidStateTransitionException If transition is invalid
     */
    public function transitionToUpdatePending(Plugin $plugin): void
    {
        $this->transition($plugin, PluginStateEnum::UPDATE_PENDING);
    }

    /**
     * @return PluginStateEnum[] Array of allowed target states
     */
    public function getAllowedTransitions(PluginStateEnum $currentState): array
    {
        $allowedValues = self::ALLOWED_TRANSITIONS[$currentState->value] ?? [];
        $allowedStates = [];

        foreach ($allowedValues as $value) {
            $allowedStates[] = PluginStateEnum::from($value);
        }

        return $allowedStates;
    }

    public function getDiagram(): string
    {
        $diagram = "Plugin State Machine:\n\n";

        foreach (self::ALLOWED_TRANSITIONS as $from => $toStates) {
            $diagram .= "  $from → " . implode(', ', $toStates) . "\n";
        }

        return $diagram;
    }
}
