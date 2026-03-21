<?php

namespace App\Core\Repository;

use App\Core\Entity\ThemeRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ThemeRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ThemeRecord::class);
    }

    public function findByName(string $name): ?ThemeRecord
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function save(ThemeRecord $record): void
    {
        $this->getEntityManager()->persist($record);
        $this->getEntityManager()->flush();
    }

    public function remove(ThemeRecord $record): void
    {
        $this->getEntityManager()->remove($record);
        $this->getEntityManager()->flush();
    }
}
