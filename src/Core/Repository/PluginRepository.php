<?php

namespace App\Core\Repository;

use App\Core\Entity\Plugin;
use App\Core\Enum\PluginStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $qb = $this->createQueryBuilder('p');

        // JSON_CONTAINS_PATH to check if plugin exists in requires
        $qb->where('JSON_CONTAINS_PATH(p.manifest, \'one\', :path) = 1')
            ->setParameter('path', '$.requires."' . $pluginName . '"')
            ->orderBy('p.name', 'ASC');

        return $qb->getQuery()->getResult();
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
