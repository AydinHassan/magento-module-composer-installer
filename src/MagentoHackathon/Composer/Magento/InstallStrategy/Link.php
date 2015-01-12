<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class Link
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 */
class Link implements InstallStrategyInterface
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
     * Resolve the mappings. If source is a folder, create mappings for every file inside it.
     * Also if destination dir is an existing folder and its base does not match the source base,
     * source should be placed inside destination.
     *
     * @param string $source
     * @param string $destination
     * @return array
     */
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

        //dir - dir
        if (is_dir($source)) {
            return $this->resolveDirectory($source, $destination);
        }

        //file - to - file
        return array(array($source, $destination));
    }

    /**
     * @param string    $source Absolute Path of source
     * @param string    $destination Absolute Path of destination
     * @param bool      $force Whether the creation should be forced (eg if it exists already)
     *
     * @return array Should return an array of files which were created
     *               Created directories should not be returned.
     */
    public function create($source, $destination, $force)
    {
        // If file exists and force is not specified, throw exception
        if (file_exists($destination)) {
            if (!$force) {
                throw new TargetExistsException($destination);
            }
            $this->fileSystem->remove($destination);
        }

        link($source, $destination);
        return array($destination);
    }

    /**
     * Build an array of mappings which should be created
     * eg. Every file in the directory
     *
     * @param string $source
     * @param string $destination
     *
     * @return array Array of all files created
     * @throws \ErrorException
     */
    protected function resolveDirectory($source, $destination)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $resolvedMappings = array();
        foreach ($iterator as $item) {
            $absoluteDestination = sprintf('%s/%s', $destination, $iterator->getSubPathName());
            if ($item->isFile()) {
                $resolvedMappings[] = array($item->getPathname(), $absoluteDestination);
            }
        }
        return $resolvedMappings;
    }
}
