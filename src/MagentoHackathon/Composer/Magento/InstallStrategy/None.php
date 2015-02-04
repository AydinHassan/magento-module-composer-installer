<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Map\Map;

/**
 * None deploy strategy
 */
class None implements InstallStrategyInterface
{


    /**
     * @param string $source
     * @param string $destination
     * @param string $absoluteSource
     * @param string $absoluteDestination
     *
     * @return array Resolved Mappings
     */
    public function resolve($source, $destination, $absoluteSource, $absoluteDestination)
    {
        return array();
    }

    /**
     * Deploy Nothing
     *
     * @param Map   $map
     * @param bool  $force
     * @throws TargetExistsException
     */
    public function create(Map $map, $force)
    {
        return;
    }
}
