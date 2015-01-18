<?php

namespace MagentoHackathon\Composer\Magento\UnInstallStrategy;

use MagentoHackathon\Composer\Magento\Util\Filesystem;

/**
 * Class UnInstallStrategy
 * @package MagentoHackathon\Composer\Magento\UnInstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class UnInstallStrategy implements UnInstallStrategyInterface
{

    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * The root dir for uninstalling from. Should be project root.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * @param FileSystem $fileSystem
     * @param string $root
     */
    public function __construct(FileSystem $fileSystem, $root)
    {
        $this->fileSystem   = $fileSystem;
        $this->root         = $root;
    }

    /**
     * UnInstall the extension given the list of install files
     *
     * @param array $files
     */
    public function unInstall(array $files)
    {
        foreach ($files as $file) {
            $this->fileSystem->unlink($file);

            if ($this->fileSystem->isDirEmpty(dirname($file))) {
                $this->fileSystem->removeEmptyDirectoriesUpToRoot(dirname($file), $this->root);
            }
        }
    }
}
