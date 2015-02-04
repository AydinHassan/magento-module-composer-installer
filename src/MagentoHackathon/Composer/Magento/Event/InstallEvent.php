<?php

namespace MagentoHackathon\Composer\Magento\Event;

use ArrayObject;
use Composer\EventDispatcher\Event;

/**
 * Class InstallEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallEvent extends Event
{
    /**
     * @var ArrayObject
     */
    protected $packages;

    /**
     * @param string             $name
     * @param ArrayObject $packages
     */
    public function __construct($name, ArrayObject $packages)
    {
        parent::__construct($name);
        $this->packages = $packages;
    }

    /**
     * @return ArrayObject
     */
    public function getPackages()
    {
        return $this->packages;
    }
}
