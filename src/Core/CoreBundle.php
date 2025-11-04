<?php

namespace App\Core;

use App\Core\DependencyInjection\Compiler\PluginCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginDoctrineCompilerPass;
use App\Core\DependencyInjection\Compiler\WidgetRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use App\Core\DependencyInjection\CoreExtension;
use function dirname;

class CoreBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new CoreExtension();
        }

        return $this->extension;
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register core system compiler passes
        $container->addCompilerPass(new WidgetRegistryCompilerPass());
        $container->addCompilerPass(new PluginCompilerPass());              // Controllers, services.yaml
        $container->addCompilerPass(new PluginDoctrineCompilerPass());     // Doctrine entities
    }
}
