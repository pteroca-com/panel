<?php

namespace App\Core\Repository;

use App\Core\Entity\Payment;
use App\Core\Enum\PaymentStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }

    public function getPaymentsCreatedAfterCount(\DateTime $date): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLastPayments(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getUserSuccessfulPayments(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :userId')
            ->andWhere('p.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', PaymentStatusEnum::PAID->value)
            ->getQuery()
            ->getResult();
    }

    public function getUserSuccessfulPaymentsCount(int $userId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user = :userId')
            ->andWhere('p.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', PaymentStatusEnum::PAID->value)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
