<?php

namespace App\Core\Repository;

use App\Core\Entity\Server;
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

    public function getServersToSuspend(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isSuspended = false')
            ->andWhere('s.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
