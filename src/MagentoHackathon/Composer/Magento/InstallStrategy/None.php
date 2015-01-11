<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

/**
 * None deploy strategy
 */
class None implements InstallStrategyInterface
{


    public function resolve($source, $destination)
    {
        return array();
    }

    /**
     * Deploy nothing
     *
     * @param string $source
     * @param string $destination
     * @param bool $force
     *
     * @return bool
     */
    public function create($source, $destination, $force)
    {
        return array();
    }
}
