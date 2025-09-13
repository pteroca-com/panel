<?php

namespace App\Core\Repository;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Log;
use App\Core\Enum\LogActionEnum;
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
}
