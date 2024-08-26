<?php

namespace App\Core\Repository;

use App\Core\Entity\Setting;
use App\Core\Enum\SettingEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 *
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getSetting(SettingEnum $settingEnum): ?string
    {
        return $this->findOneBy(['name' => $settingEnum->value])?->getValue();
    }

    public function getCurrency(): ?string
    {
        return $this->getSetting(SettingEnum::INTERNAL_CURRENCY_NAME);
    }

    public function save(Setting $setting): void
    {
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }
}
