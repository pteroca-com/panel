<?php

namespace App\Core\Repository;

use App\Core\Contract\UserInterface;
use App\Core\Entity\EmailLog;
use App\Core\Entity\Server;
use App\Core\Enum\EmailTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailLog>
 *
 * @method EmailLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailLog[]    findAll()
 * @method EmailLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailLog::class);
    }

    public function save(EmailLog $emailLog): void
    {
        $this->getEntityManager()->persist($emailLog);
        $this->getEntityManager()->flush();
    }

    public function findLastByServerAndType(Server $server, EmailTypeEnum $emailType): ?EmailLog
    {
        return $this->createQueryBuilder('el')
            ->where('el.server = :server')
            ->andWhere('el.emailType = :emailType')
            ->setParameter('server', $server)
            ->setParameter('emailType', $emailType)
            ->orderBy('el.sentAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLastByUserAndType(UserInterface $user, EmailTypeEnum $emailType): ?EmailLog
    {
        return $this->createQueryBuilder('el')
            ->where('el.user = :user')
            ->andWhere('el.emailType = :emailType')
            ->setParameter('user', $user)
            ->setParameter('emailType', $emailType)
            ->orderBy('el.sentAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByServerAndTypeInPeriod(Server $server, EmailTypeEnum $emailType, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('el')
            ->select('COUNT(el.id)')
            ->where('el.server = :server')
            ->andWhere('el.emailType = :emailType')
            ->andWhere('el.sentAt >= :since')
            ->setParameter('server', $server)
            ->setParameter('emailType', $emailType)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldLogs(\DateTimeInterface $cutoffDate): int
    {
        $qb = $this->createQueryBuilder('el')
            ->delete()
            ->where('el.sentAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate);

        return $qb->getQuery()->execute();
    }
}
