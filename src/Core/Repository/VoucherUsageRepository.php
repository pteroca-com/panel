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
            ->join('vu.voucher', 'v')
            ->where('v.code = :voucherCode')
            ->andWhere('vu.user = :userId')
            ->setParameter('voucherCode', $voucherCode)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasUsedAnyVoucher(int $userId): bool
    {
        return (bool) $this->createQueryBuilder('vu')
            ->select('COUNT(vu.id)')
            ->where('vu.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
