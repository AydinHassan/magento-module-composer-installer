<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Map\MapCollection;

/**
 * Interface InstallerInterface
 * @package MagentoHackathon\Composer\Magento\Installer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface InstallerInterface
{
    /**
     * @param PackageInterface $package
     * @param string           $packageSourceDirectory
     *
     * @return MapCollection
     */
    public function install(PackageInterface $package, $packageSourceDirectory);
}
