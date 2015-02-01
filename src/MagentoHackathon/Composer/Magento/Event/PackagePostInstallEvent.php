<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\InstalledPackage;
use MagentoHackathon\Composer\Magento\Map\MapCollection;

/**
 * Class PackagePostInstallEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackagePostInstallEvent extends Event
{
    /**
     * @var PackageInterface
     */
    protected $composerPackage;

    /**
     * @var InstalledPackage
     */
    protected $installedPackage;

    /**
     * @param PackageInterface $composerPackage
     * @param InstalledPackage $installedPackage
     */
    public function __construct(PackageInterface $composerPackage, InstalledPackage $installedPackage)
    {
        parent::__construct('package-post-install');
        $this->composerPackage  = $composerPackage;
        $this->installedPackage = $installedPackage;
    }

    /**
     * @return PackageInterface
     */
    public function getPackage()
    {
        return $this->composerPackage;
    }

    /**
     * @return MapCollection
     */
    public function getMappings()
    {
        return $this->installedPackage->getMappings();
    }
}
