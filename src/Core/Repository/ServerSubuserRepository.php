<?php

namespace App\Core\Repository;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Server;
use App\Core\Entity\ServerSubuser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServerSubuserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerSubuser::class);
    }

    public function save(ServerSubuser $serverSubuser): void
    {
        $this->getEntityManager()->persist($serverSubuser);
        $this->getEntityManager()->flush();
    }

    public function delete(ServerSubuser $serverSubuser): void
    {
        $this->getEntityManager()->remove($serverSubuser);
        $this->getEntityManager()->flush();
    }

    /**
     * @return ServerSubuser[]
     */
    public function getSubusersByServer(Server $server): array
    {
        return $this->createQueryBuilder('ss')
            ->where('ss.server = :server')
            ->setParameter('server', $server)
            ->orderBy('ss.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ServerSubuser[]
     */
    public function getSubusersByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('ss')
            ->where('ss.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ss.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSubuserByServerAndUser(Server $server, UserInterface $user): ?ServerSubuser
    {
        return $this->createQueryBuilder('ss')
            ->where('ss.server = :server')
            ->andWhere('ss.user = :user')
            ->setParameter('server', $server)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getSubusersCountByServer(Server $server): int
    {
        return $this->createQueryBuilder('ss')
            ->select('COUNT(ss.id)')
            ->where('ss.server = :server')
            ->setParameter('server', $server)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getServersCountByUser(UserInterface $user): int
    {
        return $this->createQueryBuilder('ss')
            ->select('COUNT(ss.id)')
            ->where('ss.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
