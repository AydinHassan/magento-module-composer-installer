<?php

namespace MagentoHackathon\Composer\Magento;
use MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface;

/**
 * Class Installer
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class Installer
{

    /**
     * @var InstallStrategy\InstallStrategyInterface
     */
    protected $installStrategy;

    /**
     * @var array
     */
    protected $mappings;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param InstallStrategyInterface $installStrategy
     * @param array                    $mappings
     * @param array                    $options
     */
    public function __construct(InstallStrategyInterface $installStrategy, array $mappings, array $options = array())
    {
        $this->installStrategy  = $installStrategy;
        $this->mappings         = $mappings;
        $this->options          = $options;
    }

    /**
     * Delegate installation to the particular strategy
     */
    public function install()
    {
        foreach ($this->mappings as $mapping) {
            list ($source, $destination) = $mapping;
            $this->installStrategy->create($source, $destination);
        }
    }
}