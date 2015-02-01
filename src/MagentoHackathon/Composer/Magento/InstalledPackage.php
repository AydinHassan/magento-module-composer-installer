<?php

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Map\MapCollection;

/**
 * Class InstalledPackage
 * @package MagentoHackathon\Composer\Magento
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InstalledPackage
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var MapCollection
     */
    protected $mappings;

    /**
     * @param string   $name
     * @param string   $version
     * @param MapCollection $mappings
     */
    public function __construct($name, $version, MapCollection $mappings)
    {
        $this->name     = $name;
        $this->version  = $version;
        $this->mappings = $mappings;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getUniqueName()
    {
        return sprintf('%s-%s', $this->getName(), $this->getVersion());
    }

    /**
     * @return MapCollection
     */
    public function getMappings()
    {
        return $this->mappings;
    }
}
