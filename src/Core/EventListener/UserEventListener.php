<?php

namespace App\Core\EventListener;

use App\Core\Entity\User;
use App\Core\Event\User\Registration\UserAboutToBeCreatedEvent;
use App\Core\Event\User\Registration\UserCreatedEvent;
use App\Core\Event\User\Registration\UserRegisteredEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postFlush)]
class UserEventListener
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

        // Tylko dla nowych użytkowników (nie ma jeszcze ID)
        if ($entity->getId() !== null) {
            return;
        }

        $event = new UserAboutToBeCreatedEvent($entity);
        $this->eventDispatcher->dispatch($event);

        // Jeśli event został odrzucony, możemy rzucić wyjątek
        if ($event->isRejected()) {
            throw new \RuntimeException($event->getRejectionReason() ?? 'User creation rejected');
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Zapamiętaj użytkownika do postFlush
        $this->persistedUsers[$entity->getId()] = $entity;

        $event = new UserCreatedEvent(
            $entity->getId(),
            $entity->getEmail()
        );
        $this->eventDispatcher->dispatch($event);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // UWAGA KRYTYCZNA: Ten listener jest SINGLETON - shared state może powodować wycieki między requestami!
        // Zabezpieczenie: emituj eventy tylko raz per transakcja
        
        if (empty($this->persistedUsers) || $this->eventsFiredInCurrentTransaction) {
            return;
        }

        // Ustaw flagę PRZED emisją, aby uniknąć pętli jeśli postFlush zostanie wywołany ponownie
        $this->eventsFiredInCurrentTransaction = true;

        // Skopiuj tablicę i wyczyść natychmiast, aby uniknąć problemów z kolejnymi wywołaniami
        $usersToProcess = $this->persistedUsers;
        $this->persistedUsers = [];

        // Emituj UserRegisteredEvent dla wszystkich nowo utworzonych użytkowników
        foreach ($usersToProcess as $user) {
            $event = new UserRegisteredEvent(
                $user->getId(),
                $user->getEmail(),
                $user->isVerified()
            );
            $this->eventDispatcher->dispatch($event);
        }

        // Zresetuj flagę po zakończeniu
        $this->eventsFiredInCurrentTransaction = false;
    }
}
