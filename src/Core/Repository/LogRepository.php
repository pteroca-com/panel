<?php

namespace App\Core\Repository;

use App\Core\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    public function save(Log $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    public function deleteOldLogs(\DateTimeInterface $cutoffDate): int
    {
        $qb = $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate);

        return $qb->getQuery()->execute();
    }
}
