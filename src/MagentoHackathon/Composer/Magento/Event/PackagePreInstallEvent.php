<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;

/**
 * Class PackagePreInstallEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackagePreInstallEvent extends Event
{
    /**
     * @var PackageInterface
     */
    protected $package;

    /**
     * @param PackageInterface $package
     */
    public function __construct(PackageInterface $package)
    {
        parent::__construct('package-pre-install');
        $this->package = $package;
    }

    /**
     * @return PackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }
}
