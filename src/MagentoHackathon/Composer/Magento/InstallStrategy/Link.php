<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\Map\Map;
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
     * @param string $absoluteSource
     * @param string $absoluteDestination
     *
     * @return array Resolved Mappings
     */
    public function resolve($source, $destination, $absoluteSource, $absoluteDestination)
    {
        if (is_dir($absoluteDestination)
            && !$this->fileSystem->sourceAndDestinationBaseMatch($source, $destination)
        ) {
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
        if (is_dir($absoluteSource)) {
            return $this->resolveDirectory($source, $destination, $absoluteSource, $absoluteDestination);
        }

        //file - to - file
        return [[$source, $destination]];
    }

    /**
     * Build an array of mappings which should be created
     * eg. Every file in the directory
     *
     * @param string $source
     * @param string $destination
     * @param string $absoluteSource
     * @param string $absoluteDestination
     *
     * @return array Array of resolved mappings
     */
    protected function resolveDirectory($source, $destination, $absoluteSource, $absoluteDestination)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteSource, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $resolvedMappings = [];
        foreach ($iterator as $item) {
            /** @var SplFileinfo $item */
            if ($item->isFile()) {
                $resolvedMappings[] = [
                    sprintf('%s/%s', $source, $iterator->getSubPathname()),
                    sprintf('%s/%s', $destination, $iterator->getSubPathname()),
                ];
            }
        }
        return $resolvedMappings;
    }

    /**
     * @param Map   $map
     * @param bool  $force
     * @throws TargetExistsException
     */
    public function create(Map $map, $force)
    {
        // If file exists and force is not specified, throw exception
        if (file_exists($map->getAbsoluteDestination())) {
            if (!$force) {
                throw new TargetExistsException($map->getAbsoluteDestination());
            }
            $this->fileSystem->remove($map->getAbsoluteDestination());
        }

        link($map->getAbsoluteSource(), $map->getAbsoluteDestination());
    }
}
