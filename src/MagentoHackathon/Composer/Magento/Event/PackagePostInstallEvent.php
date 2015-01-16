<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;

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
    protected $package;

    /**
     * @var array
     */
    protected $installedFiles;

    /**
     * @param PackageInterface $package
     * @param array            $installedFiles
     */
    public function __construct(PackageInterface $package, array $installedFiles)
    {
        parent::__construct('package-post-install');
        $this->package          = $package;
        $this->installedFiles   = $installedFiles;
    }

    /**
     * @return PackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @return array
     */
    public function getInstalledFiles()
    {
        return $this->installedFiles;
    }
}
