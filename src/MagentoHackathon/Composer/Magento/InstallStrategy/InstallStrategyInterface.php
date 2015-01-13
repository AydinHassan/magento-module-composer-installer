<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\Map\Map;

/**
 * Interface InstallStrategyInterface
 * @package MagentoHackathon\Composer\Magento\InstallStrategyOld
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface InstallStrategyInterface
{

    /**
     * Resolve the mappings. Should return an array of mappings which
     * should be created. Eg in the case of copy, if source is a directory, then each file inside that directory
     * should be returned as a mapping.
     *
     * @param string $source
     * @param string $destination
     * @param string $absoluteSource
     * @param string $absoluteDestination
     *
     * @return array Resolved Mappings
     */
    public function resolve($source, $destination, $absoluteSource, $absoluteDestination);

    /**
     * @param Map   $map Map contains the relative and absolute source and destination
     * @param bool  $force Whether the creation should be forced (eg if it exists already)
     * @throws TargetExistsException If a
     *
     */
    public function create(Map $map, $force);
}
