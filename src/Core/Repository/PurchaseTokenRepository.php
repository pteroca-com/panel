<?php

namespace App\Core\Repository;

use App\Core\Contract\UserInterface;
use App\Core\Entity\PurchaseToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PurchaseTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseToken::class);
    }

    public function save(PurchaseToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function findValidToken(string $token, UserInterface $user, string $type): ?PurchaseToken
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.token = :token')
            ->andWhere('pt.user = :user')
            ->andWhere('pt.type = :type')
            ->setParameter('token', $token)
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteToken(PurchaseToken $token): void
    {
        $this->getEntityManager()->remove($token);
        $this->getEntityManager()->flush();
    }

    public function deleteExpiredTokens(\DateTime $before): int
    {
        return $this->createQueryBuilder('pt')
            ->delete()
            ->where('pt.createdAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    public function deleteUserTokensByType(UserInterface $user, string $type): void
    {
        $this->createQueryBuilder('pt')
            ->delete()
            ->where('pt.user = :user')
            ->andWhere('pt.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->execute();
    }
}
