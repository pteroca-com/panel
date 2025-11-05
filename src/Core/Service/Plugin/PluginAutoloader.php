<?php

namespace App\Core\Service\Plugin;

use App\Core\Entity\Plugin;

class PluginAutoloader
{
    private array $registeredNamespaces = [];

    public function __construct(
        private readonly string $projectDir,
    ) {}

    public function registerPlugin(Plugin $plugin): bool
    {
        if (!$plugin->isEnabled()) {
            return false;
        }

        $namespace = $this->getPluginNamespace($plugin->getName());
        $path = $this->getPluginSrcPath($plugin->getName());

        if (!is_dir($path)) {
            return false;
        }

        return $this->registerNamespace($namespace, $path);
    }

    public function unregisterPlugin(Plugin $plugin): void
    {
        $namespace = $this->getPluginNamespace($plugin->getName());
        $this->unregisterNamespace($namespace);
    }

    private function registerNamespace(string $namespace, string $path): bool
    {
        if (isset($this->registeredNamespaces[$namespace])) {
            return false; // Already registered
        }

        spl_autoload_register(function ($class) use ($namespace, $path) {
            // Check if class belongs to this namespace
            if (!str_starts_with($class, $namespace)) {
                return;
            }

            // Get relative class name
            $relativeClass = substr($class, strlen($namespace));

            // Replace namespace separators with directory separators
            $file = $path . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            // Require if exists
            if (file_exists($file)) {
                require $file;
            }
        });

        $this->registeredNamespaces[$namespace] = $path;

        return true;
    }

    private function unregisterNamespace(string $namespace): void
    {
        unset($this->registeredNamespaces[$namespace]);
    }

    private function getPluginNamespace(string $pluginName): string
    {
        // Convert "hello-world" to "HelloWorld"
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $pluginName)));

        return "Plugins\\$className\\";
    }

    private function getPluginSrcPath(string $pluginName): string
    {
        return $this->projectDir . '/plugins/' . $pluginName . '/src';
    }

    /**
     * @return array<string, string> Namespace => Path
     */
    public function getRegisteredNamespaces(): array
    {
        return $this->registeredNamespaces;
    }

    public function isNamespaceRegistered(string $namespace): bool
    {
        return isset($this->registeredNamespaces[$namespace]);
    }
}
