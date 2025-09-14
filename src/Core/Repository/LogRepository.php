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

    public function findLastVerificationSent(UserInterface $user): ?Log
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.actionId IN (:actions)')
            ->setParameter('user', $user)
            ->setParameter('actions', [
                LogActionEnum::EMAIL_VERIFICATION_SENT->name,
                LogActionEnum::EMAIL_VERIFICATION_RESENT->name
            ])
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
