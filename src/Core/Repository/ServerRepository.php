<?php

namespace App\Core\Repository;

use App\Core\Entity\Server;
use App\Core\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    public function save(Server $server): void
    {
        $this->getEntityManager()->persist($server);
        $this->getEntityManager()->flush();
    }

    public function delete(Server $server): void
    {
        $this->getEntityManager()->remove($server);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Server[]
     */
    public function getServersExpiredBefore(\DateTime $expiresBefore): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isSuspended = true')
            ->andWhere('s.expiresAt < :expiresBefore')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('expiresBefore', $expiresBefore)
            ->getQuery()
            ->getResult();
    }

    public function getServersToSuspend(\DateTime $expiresBefore): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isSuspended = false')
            ->andWhere('s.expiresAt < :expiresBefore')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('expiresBefore', $expiresBefore)
            ->getQuery()
            ->getResult();
    }

    public function getActiveServersByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.isSuspended = false')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getActiveServersCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.isSuspended = false')
            ->andWhere('s.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getActiveServer(int $id): ?Server
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :id')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();
    }

    public function getAllServersOwnedCount(int $userId): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
