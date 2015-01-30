<?php

namespace MagentoHackathon\Composer\Magento\UnInstallStrategy;

use MagentoHackathon\Composer\Magento\Map\MapCollection;

/**
 * Interface UnInstallStrategyInterface
 * @package MagentoHackathon\Composer\Magento\UnInstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
interface UnInstallStrategyInterface
{
    /**
     * UnInstall the extension given the list of install files
     * @param MapCollection $collection
     */
    public function unInstall(MapCollection $collection);
}
