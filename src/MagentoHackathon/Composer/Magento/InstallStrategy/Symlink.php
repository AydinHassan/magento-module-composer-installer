<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Symlink deploy strategy
 */
class Symlink implements InstallStrategyInterface
{
    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @param FileSystem $fileSystem
     */
    public function __construct(FileSystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * Resolve the mappings.
     *
     * For symlinks, if the destination exists and is a directory, the symlink
     * should be created *inside* destination
     *
     * @param string $source
     * @param string $destination
     * @return array
     */
    public function resolve($source, $destination)
    {
        if (is_dir($destination)) {
            $destination = sprintf('%s/%s', $destination, basename($source));
        }

        return array(array($source, $destination));
    }

    /**
     * @param string $source
     * @param string $destination
     * @param bool $force
     *
     * @return array
     * @throws TargetExistsException
     */
    public function create($source, $destination, $force)
    {
        //if destination exists should we overwrite it?
        if (is_dir($destination) && $this->fileSystem->sourceAndDestinationBaseMatch($source, $destination)) {
            if (!$force) {
                throw new TargetExistsException($destination);
            }
            $this->fileSystem->remove($destination);
        }

        return $this->symlink($source, $destination, $force);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param bool $force
     *
     * @return array Array of all the files created
     * @throws \TargetExistsException
     */
    protected function symlink($source, $destination, $force)
    {
        if (is_link($destination)) {
            if ($this->fileSystem->symLinkPointsToCorrectLocation($destination, $source)) {
                return array();
            }
            $this->fileSystem->remove($destination);
        }

        $this->fileSystem->ensureDirectoryExists(dirname($destination));

        // If file exists and force is not specified, throw exception unless FORCE is set
        // existing symlinks are already handled
        if (file_exists($destination)) {
            if (!$force) {
                throw new TargetExistsException($destination);
            }
            $this->fileSystem->remove($destination);
        }

        $this->fileSystem->createSymlink($source, $destination);
        return array($destination);
    }
}
