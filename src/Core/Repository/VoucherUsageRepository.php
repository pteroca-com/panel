<?php

namespace App\Core\Repository;

use App\Core\Entity\VoucherUsage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VoucherUsageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoucherUsage::class);
    }

    public function save(VoucherUsage $voucherUsage): void
    {
        $this->getEntityManager()->persist($voucherUsage);
        $this->getEntityManager()->flush();
    }

    public function hasUsedVoucher(string $voucherCode, int $userId): bool
    {
        return (bool) $this->createQueryBuilder('vu')
            ->select('COUNT(vu.id)')
            ->where('vu.voucherCode = :voucherCode')
            ->andWhere('vu.userId = :userId')
            ->setParameter('voucherCode', $voucherCode)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
