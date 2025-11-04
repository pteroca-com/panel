<?php

namespace App\Core\Repository;

use App\Core\Entity\Plugin;
use App\Core\Enum\PluginStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

class PluginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plugin::class);
    }

    public function findByName(string $name): ?Plugin
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @return Plugin[]
     */
    public function findByState(PluginStateEnum $state): array
    {
        return $this->findBy(['state' => $state], ['name' => 'ASC']);
    }

    /**
     * @return Plugin[]
     */
    public function findEnabled(): array
    {
        return $this->findByState(PluginStateEnum::ENABLED);
    }

    /**
     * @return Plugin[]
     */
    public function findDisabled(): array
    {
        return $this->findByState(PluginStateEnum::DISABLED);
    }

    /**
     * @return Plugin[]
     */
    public function findFaulted(): array
    {
        return $this->findByState(PluginStateEnum::FAULTED);
    }

    /**
     * @return Plugin[]
     */
    public function findPendingUpdate(): array
    {
        return $this->findByState(PluginStateEnum::UPDATE_PENDING);
    }

    /**
     * @return Plugin[]
     */
    public function findRegistered(): array
    {
        return $this->findByState(PluginStateEnum::REGISTERED);
    }

    /**
     * @return Plugin[]
     */
    public function findByCapability(string $capability, ?PluginStateEnum $state = null): array
    {
        $qb = $this->createQueryBuilder('p');

        // JSON_CONTAINS to check if capability exists in manifest
        $qb->where('JSON_CONTAINS(p.manifest, :capability, \'$.capabilities\') = 1')
            ->setParameter('capability', json_encode($capability));

        if ($state !== null) {
            $qb->andWhere('p.state = :state')
                ->setParameter('state', $state);
        }

        $qb->orderBy('p.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Plugin[]
     */
    public function findEnabledByCapability(string $capability): array
    {
        return $this->findByCapability($capability, PluginStateEnum::ENABLED);
    }

    public function existsByName(string $name): bool
    {
        return $this->count(['name' => $name]) > 0;
    }

    public function countByState(PluginStateEnum $state): int
    {
        return $this->count(['state' => $state]);
    }

    /**
     * @return array<string, int> Map of state value => count
     */
    public function getStateStatistics(): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.state', 'COUNT(p.id) as count')
            ->groupBy('p.state');

        $results = $qb->getQuery()->getResult();

        $statistics = [];
        foreach ($results as $result) {
            $state = $result['state'];
            if ($state instanceof PluginStateEnum) {
                $statistics[$state->value] = (int) $result['count'];
            }
        }

        return $statistics;
    }

    /**
     * @return Plugin[] Plugins that have $pluginName in their 'requires' manifest field
     */
    public function findDependents(string $pluginName): array
    {
        // Use native SQL because Doctrine DQL doesn't support JSON_CONTAINS_PATH
        $sql = "SELECT p.* FROM plugin p
                WHERE JSON_CONTAINS_PATH(p.manifest, 'one', :path) = 1
                ORDER BY p.name ASC";

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Plugin::class, 'p');
        $rsm->addFieldResult('p', 'id', 'id');
        $rsm->addFieldResult('p', 'name', 'name');
        $rsm->addFieldResult('p', 'display_name', 'displayName');
        $rsm->addFieldResult('p', 'version', 'version');
        $rsm->addFieldResult('p', 'author', 'author');
        $rsm->addFieldResult('p', 'description', 'description');
        $rsm->addFieldResult('p', 'state', 'state');
        $rsm->addFieldResult('p', 'manifest', 'manifest');
        $rsm->addFieldResult('p', 'enabled_at', 'enabledAt');
        $rsm->addFieldResult('p', 'disabled_at', 'disabledAt');
        $rsm->addFieldResult('p', 'fault_reason', 'faultReason');
        $rsm->addFieldResult('p', 'created_at', 'createdAt');
        $rsm->addFieldResult('p', 'updated_at', 'updatedAt');

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('path', '$.requires."' . $pluginName . '"');

        return $query->getResult();
    }

    public function save(Plugin $plugin, bool $flush = true): void
    {
        $this->getEntityManager()->persist($plugin);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Plugin $plugin, bool $flush = true): void
    {
        $this->getEntityManager()->remove($plugin);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
