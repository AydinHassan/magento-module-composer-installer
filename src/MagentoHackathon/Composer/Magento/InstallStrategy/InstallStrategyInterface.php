<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

/**
 * Interface InstallStrategyInterface
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface InstallStrategyInterface
{
    /**
     * @param string $source Absolute Path of source
     * @param string $destination Absolute Path of destination
     * @param bool $force Whether the creation should be forced (eg if it exists already)
     *
     * @return array Should return an array of files which were created
     *               Created directories should not be returned.
     */
    public function create($source, $destination, $force);
}
