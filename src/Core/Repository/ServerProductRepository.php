<?php

namespace App\Core\Repository;

use App\Core\Entity\ServerProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServerProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerProduct::class);
    }

    public function save(ServerProduct $serverProduct): void
    {
        $this->getEntityManager()->persist($serverProduct);
        $this->getEntityManager()->flush();
    }
}
