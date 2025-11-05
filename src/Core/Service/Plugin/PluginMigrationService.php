<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

readonly class PluginMigrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
        private string                 $projectDir,
    ) {}

    /**
     * @throws Exception
     */
    public function executeMigrations(Plugin $plugin): array
    {
        if (!$plugin->hasCapability('migrations')) {
            $this->logger->debug("Plugin {$plugin->getName()} does not have 'migrations' capability");
            return ['executed' => 0, 'skipped' => true];
        }

        $migrationsPath = $this->getMigrationsPath($plugin->getName());

        if (!is_dir($migrationsPath)) {
            $this->logger->debug("Migrations directory not found for plugin {$plugin->getName()}");
            return ['executed' => 0, 'skipped' => true];
        }

        try {
            // Create migration configuration for this plugin
            $configuration = $this->createMigrationConfiguration($plugin);
            $dependencyFactory = DependencyFactory::fromEntityManager(
                new ExistingConfiguration($configuration),
                new ExistingEntityManager($this->entityManager)
            );

            // Ensure metadata storage table exists
            // ensureInitialized() is idempotent - it won't recreate if already exists
            $metadataStorage = $dependencyFactory->getMetadataStorage();
            $metadataStorage->ensureInitialized();

            // Get migrator
            $migrator = $dependencyFactory->getMigrator();

            // Get all available migrations
            $availableMigrations = $dependencyFactory->getMigrationRepository()->getMigrations();

            if ($availableMigrations->count() === 0) {
                $this->logger->info("No migrations found for plugin {$plugin->getName()}");
                return ['executed' => 0, 'skipped' => true];
            }

            // Get already executed migrations from doctrine_migration_versions table
            $executedMigrations = $dependencyFactory->getMetadataStorage()->getExecutedMigrations();
            $executedVersions = [];
            foreach ($executedMigrations->getItems() as $executedMigration) {
                $executedVersions[] = (string) $executedMigration->getVersion();
            }

            // Find migrations that haven't been executed yet
            $pendingVersions = [];
            foreach ($availableMigrations->getItems() as $migration) {
                $versionString = (string) $migration->getVersion();
                if (!in_array($versionString, $executedVersions, true)) {
                    $pendingVersions[] = $migration->getVersion();
                }
            }

            // If no pending migrations, skip
            if (empty($pendingVersions)) {
                $this->logger->info("All migrations already executed for plugin {$plugin->getName()}");
                return ['executed' => 0, 'skipped' => true];
            }

            // Create plan only for pending migrations
            $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanForVersions(
                $pendingVersions,
                Direction::UP
            );

            // Create migrator configuration
            $migratorConfiguration = new MigratorConfiguration();
            $migratorConfiguration->setAllOrNothing(false); // Execute migrations one by one
            $migratorConfiguration->setTimeAllQueries(true); // Track query execution time

            $migratedVersions = $migrator->migrate($plan, $migratorConfiguration);
            $executed = count($migratedVersions);

            $this->logger->info("Executed $executed migrations for plugin {$plugin->getName()}");

            // Extract migration version names from array keys
            $versionStrings = array_keys($migratedVersions);

            return [
                'executed' => $executed,
                'skipped' => false,
                'migrations' => $versionStrings,
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to execute migrations for plugin {$plugin->getName()}: {$e->getMessage()}");
            throw new RuntimeException(
                "Failed to execute migrations for plugin {$plugin->getName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function hasPendingMigrations(Plugin $plugin): bool
    {
        if (!$plugin->hasCapability('migrations')) {
            return false;
        }

        $migrationsPath = $this->getMigrationsPath($plugin->getName());

        if (!is_dir($migrationsPath)) {
            return false;
        }

        try {
            $configuration = $this->createMigrationConfiguration($plugin);
            $dependencyFactory = DependencyFactory::fromEntityManager(
                new ExistingConfiguration($configuration),
                new ExistingEntityManager($this->entityManager)
            );

            $availableMigrations = $dependencyFactory->getMigrationRepository()->getMigrations();
            $executedMigrations = $dependencyFactory->getMetadataStorage()->getExecutedMigrations();

            return $availableMigrations->count() > count($executedMigrations->getItems());

        } catch (Exception $e) {
            $this->logger->error("Failed to check pending migrations for plugin {$plugin->getName()}: {$e->getMessage()}");
            return false;
        }
    }

    private function getMigrationsPath(string $pluginName): string
    {
        return $this->projectDir . '/plugins/' . $pluginName . '/Migrations';
    }

    private function createMigrationConfiguration(Plugin $plugin): Configuration
    {
        $configuration = new Configuration();

        // Set migrations paths
        $migrationsPath = $this->getMigrationsPath($plugin->getName());
        $configuration->addMigrationsDirectory(
            $this->getPluginMigrationNamespace($plugin->getName()),
            $migrationsPath
        );

        $storageConfiguration = new TableMetadataStorageConfiguration();
        $storageConfiguration->setTableName('doctrine_migration_versions');
        $configuration->setMetadataStorageConfiguration($storageConfiguration);

        return $configuration;
    }

    private function getPluginMigrationNamespace(string $pluginName): string
    {
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));
        return "Plugins\\$className\\Migrations";
    }

    public function getCurrentVersion(Plugin $plugin): ?string
    {
        if (!$plugin->hasCapability('migrations')) {
            return null;
        }

        try {
            $configuration = $this->createMigrationConfiguration($plugin);
            $dependencyFactory = DependencyFactory::fromEntityManager(
                new ExistingConfiguration($configuration),
                new ExistingEntityManager($this->entityManager)
            );

            $executedMigrations = $dependencyFactory->getMetadataStorage()->getExecutedMigrations();

            if ($executedMigrations->count() === 0) {
                return null;
            }

            $items = $executedMigrations->getItems();
            $lastMigration = end($items);

            return $lastMigration ? (string) $lastMigration->getVersion() : null;

        } catch (Exception $e) {
            $this->logger->error("Failed to get current version for plugin {$plugin->getName()}: {$e->getMessage()}");
            return null;
        }
    }
}
