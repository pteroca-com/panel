<?php

namespace App\Core;

use App\Core\DependencyInjection\Compiler\PluginCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginConsoleCommandCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginCronTaskCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginDoctrineCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginEventSubscriberCompilerPass;
use App\Core\DependencyInjection\Compiler\WidgetRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use App\Core\DependencyInjection\CoreExtension;

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
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register core system compiler passes
        $container->addCompilerPass(new WidgetRegistryCompilerPass());

        // Register plugin compiler passes
        // NOTE: Twig and Translation registration moved to runtime (PluginBootListener)
        $container->addCompilerPass(new PluginCompilerPass());
        $container->addCompilerPass(new PluginDoctrineCompilerPass());
        $container->addCompilerPass(new PluginEventSubscriberCompilerPass());
        $container->addCompilerPass(new PluginConsoleCommandCompilerPass());
        $container->addCompilerPass(new PluginCronTaskCompilerPass());
    }
}
