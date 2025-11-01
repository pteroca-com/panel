<?php

namespace App\Core;

use App\Core\DependencyInjection\Compiler\PluginCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginDoctrineCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginTranslationCompilerPass;
use App\Core\DependencyInjection\Compiler\PluginTwigCompilerPass;
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

        // Register plugin compiler passes
        $container->addCompilerPass(new PluginCompilerPass());
        $container->addCompilerPass(new PluginDoctrineCompilerPass());
        $container->addCompilerPass(new PluginTwigCompilerPass());
        $container->addCompilerPass(new PluginTranslationCompilerPass());
    }
}
