<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use GlobIterator;
use LogicException;
use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\SourceNotExistsException;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;

/**
 * Simple class to expand glob mappings to simple file mappings
 *
 * Class GlobExpander
 * @package MagentoHackathon\Composer\Magento\InstallStrategyOld
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
final class GlobResolver
{

    /**
     * Resolve glob mappings to file -> file mappings
     *
     * @param MapCollection $maps
     *
     * @return MapCollection
     */
    public function resolve(MapCollection $maps)
    {

        $updatedMappings = [];
        foreach ($maps as $mapping) {
            /** @var Map $mapping */
            if (file_exists($mapping->getAbsoluteSource())) {
                //file is a file, we don't care about this
                $updatedMappings[] = $mapping;
                continue;
            }

            //not a file, is it a glob?
            $iterator = new GlobIterator($mapping->getAbsoluteSource());

            try {
                if (!$iterator->count()) {
                    //we just skip this if there are no results
                    //if there are no results should we just remove this mapping?
                    //it kind of makes sense to as it is not necessarily an error
                    //if a glob produces no results
                    $updatedMappings[] = $mapping;
                    continue;
                }
            } catch (LogicException $e) {
                /**
                 * This a PHP bug where a LogicException is thrown if no files exist
                 * @link https://bugs.php.net/bug.php?id=55701
                 */
                $updatedMappings[] = $mapping;
                continue;
            }

            //add each glob as a separate mapping
            foreach ($iterator as $globResult) {
                $updatedMappings[] = $this->processMapping($globResult, $mapping);
            }
        }

        return new MapCollection($updatedMappings);
    }

    /**
     * @param \SplFileInfo $globMatch
     * @param Map $map
     *
     * @return Map
     */
    protected function processMapping(\SplFileInfo $globMatch, Map $map)
    {
        $absolutePath = $globMatch->getPathname();

        //get the relative path to this file/dir - strip of the source path
        //+1 to strip leading slash
        $source       = substr($absolutePath, strlen($map->getSourceRoot()) + 1);
        $destination  = sprintf('%s/%s', $map->getDestination(), $globMatch->getFilename());

        return new Map($source, $destination, $map->getSourceRoot(), $map->getDestinationRoot());
    }
}
