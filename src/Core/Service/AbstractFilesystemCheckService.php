<?php

namespace App\Core\Service;

use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractFilesystemCheckService
{
    public function __construct(
        protected readonly Filesystem $filesystem,
    ) {}

    /**
     * Returns list of relative paths that are not writable by the web server.
     * Empty array means all required permissions are OK.
     */
    public function getUnwritablePaths(): array
    {
        $unwritable = [];

        foreach ($this->getRequiredPaths() as $absolutePath => $relativePath) {
            if (!$this->filesystem->exists($absolutePath)) {
                try {
                    $this->filesystem->mkdir($absolutePath, 0755);
                } catch (\Exception) {
                    $unwritable[] = $relativePath;
                    continue;
                }
            }

            if (!is_writable($absolutePath)) {
                $unwritable[] = $relativePath;
            }
        }

        return $unwritable;
    }

    /**
     * Returns array of [absolutePath => relativePath] entries to check.
     */
    abstract protected function getRequiredPaths(): array;
}
