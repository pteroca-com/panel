<?php

namespace App\Core\Repository;

use App\Core\Entity\ServerLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServerLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerLog::class);
    }

    public function save(ServerLog $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    public function deleteServerLogs(int $serverId): void
    {
        $serverLogs = $this->findBy(['server' => $serverId]);
        foreach ($serverLogs as $serverLog) {
            $this->getEntityManager()->remove($serverLog);
        }
        $this->getEntityManager()->flush();
    }

    public function deleteOldLogs(\DateTimeInterface $cutoffDate): int
    {
        $qb = $this->createQueryBuilder('sl')
            ->delete()
            ->where('sl.createdAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate);

        return $qb->getQuery()->execute();
    }
}
