<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Symlink deploy strategy
 */
class Copy implements InstallStrategyInterface
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

    public function resolve($source, $destination)
    {
        if (is_dir($destination) && !$this->fileSystem->sourceAndDestinationBaseMatch($source, $destination)) {
            // If the destination exists and is a directory
            // and basename of source and destination are not equal that means we want to copy
            // source into destination, not to destination
            // eg. src: code/Some_Module.xml dest: app/etc/modules
            // would result in Some_Module.xml being placed inside: app/etc/modules
            // - so: app/etc/modules/Some_Module.xml
            //
            $destination = sprintf('%s/%s', $destination, basename($source));
        }

        if (is_dir($source)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            $resolvedMappings = array();
            foreach ($iterator as $item) {
                $absoluteDestination = sprintf('%s/%s', $destination, $iterator->getSubPathName());
                $resolvedMappings[] = array($item, $absoluteDestination);
            }
            return $resolvedMappings;
        } else {
            return array(array($source, $destination));
        }
    }


    public function create($source, $destination)
    {
        return copy($source, $destination);
    }

    /**
     * copy files
     *
     * @param string $source
     * @param string $destination
     * @param bool $force
     *
     * @return array Array of all the files created
     * @throws \ErrorException
     */
    public function create($source, $destination, $force)
    {
        if (is_dir($destination) && !$this->fileSystem->sourceAndDestinationBaseMatch($source, $destination)) {
            // If the destination exists and is a directory
            // and basename of source and destination are not equal that means we want to copy
            // source into destination, not to destination
            // eg. src: code/Some_Module.xml dest: app/etc/modules
            // would result in Some_Module.xml being placed inside: app/etc/modules
            // - so: app/etc/modules/Some_Module.xml
            //
            $destination = sprintf('%s/%s', $destination, basename($source));
        }

        return $this->copy($source, $destination, $force);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param bool $force
     *
     * @return array Array of all the files created
     * @throws \ErrorException
     */
    public function copy($source, $destination, $force)
    {
        //Create all directories up to one below the target if they don't exist
        $this->fileSystem->ensureDirectoryExists(dirname($destination));

        if (is_dir($source)) {
            return $this->copyDirectoryToDirectory($source, $destination);
        }

        // If file exists and force is not specified, throw exception
        if (file_exists($destination)) {
            if (!$force) {
                throw new \ErrorException(
                    sprintf('Target %s already exists (set extra.magento-force to override)', $destination)
                );
            }
            unlink($destination);
        }

        $this->copyFileToFile($source, $destination);
        return array($destination);
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return array Array of all files created
     * @throws \ErrorException
     */
    protected function copyDirectoryToDirectory($source, $destination)
    {
        // First create destination folder if it doesn't exist
        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $createdFiles = array();
        foreach ($iterator as $item) {
            $absoluteDestination = sprintf('%s/%s', $destination, $iterator->getSubPathName());
            if ($item->isDir()) {
                if (!file_exists($absoluteDestination)) {
                    mkdir($absoluteDestination, 0777, true);
                }
            } else {
                $createdFiles[] = $this->copyFileToFile($item, $absoluteDestination);
            }
            if (!is_readable($absoluteDestination)) {
                throw new \ErrorException(sprintf('Could not create %s', $absoluteDestination));
            }
        }
        return $createdFiles;
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    protected function copyFileToFile($source, $destination)
    {
        return copy($source, $destination);
    }
}
