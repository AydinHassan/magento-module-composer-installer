<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;

/**
 * Class InstallEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallEvent extends Event
{
    /**
     * @var PackageInterface
     */
    protected $packages;

    /**
     * @param string             $name
     * @param PackageInterface[] $packages
     */
    public function __construct($name, array $packages)
    {
        parent::__construct($name);
        $this->packages  = $packages;
    }

    /**
     * @return PackageInterface
     */
    public function getPackages()
    {
        return $this->packages;
    }
}
