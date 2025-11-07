<?php

namespace App\Core\EventSubscriber\User;

use App\Core\Entity\User;
use App\Core\Event\User\Registration\UserAboutToBeCreatedEvent;
use App\Core\Event\User\Registration\UserCreatedEvent;
use App\Core\Event\User\Registration\UserRegisteredEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use RuntimeException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postFlush)]
#[AsEventListener(event: KernelEvents::TERMINATE)]
class UserEventSubscriber
{
    private array $persistedUsers = [];
    private bool $eventsFiredInCurrentTransaction = false;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Only for new users (no ID assigned yet)
        if ($entity->getId() !== null) {
            return;
        }

        $event = new UserAboutToBeCreatedEvent($entity);
        $this->eventDispatcher->dispatch($event);

        // If event was rejected, throw an exception
        if ($event->isRejected()) {
            throw new RuntimeException($event->getRejectionReason() ?? 'User creation rejected');
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Remember user for postFlush processing
        $this->persistedUsers[$entity->getId()] = $entity;

        $event = new UserCreatedEvent(
            $entity->getId(),
            $entity->getEmail()
        );
        $this->eventDispatcher->dispatch($event);
    }

    public function postFlush(): void
    {
        // CRITICAL: This listener is a SINGLETON - shared state can cause leaks between requests!
        // Safeguard: emit events only once per transaction

        if (empty($this->persistedUsers) || $this->eventsFiredInCurrentTransaction) {
            return;
        }

        // Set flag BEFORE emission to avoid loops if postFlush is called again
        $this->eventsFiredInCurrentTransaction = true;

        // Copy array and clear immediately to avoid issues with subsequent calls
        $usersToProcess = $this->persistedUsers;
        $this->persistedUsers = [];

        try {
            // Emit UserRegisteredEvent for all newly created users
            foreach ($usersToProcess as $user) {
                $event = new UserRegisteredEvent(
                    $user->getId(),
                    $user->getEmail(),
                    $user->isVerified()
                );
                $this->eventDispatcher->dispatch($event);
            }
        } finally {
            // ALWAYS reset flag, even on exception
            $this->eventsFiredInCurrentTransaction = false;
        }
    }

    public function onKernelTerminate(): void
    {
        // Safety net: clear state after request completion
        // This ensures no state leaks between requests in long-running processes
        $this->persistedUsers = [];
        $this->eventsFiredInCurrentTransaction = false;
    }
}
