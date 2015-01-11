<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract;
use MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class InstallStrategyFactory
 * @package MagentoHackathon\Composer\Magento\Deploystrategy
 */
class InstallStrategyFactory
{

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var ParserFactoryInterface
     */
    protected $parserFactory;

    /**
     * @var array
     */
    protected static $strategies = array(
        'copy'      => '\MagentoHackathon\Composer\Magento\InstallStrategy\Copy',
        'symlink'   => '\MagentoHackathon\Composer\Magento\InstallStrategy\Symlink',
        'link'      => '\MagentoHackathon\Composer\Magento\InstallStrategy\Link',
        'none'      => '\MagentoHackathon\Composer\Magento\InstallStrategy\None',
    );

    /**
     * @param ProjectConfig $config
     */
    public function __construct(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param PackageInterface $package
     * @return InstallStrategyInterface
     */
    public function make(PackageInterface $package)
    {
        $strategyName = $this->config->getDeployStrategy();
        if ($this->config->hasDeployStrategyOverwrite()) {
            $moduleSpecificDeployStrategies = $this->config->getDeployStrategyOverwrite();

            if (isset($moduleSpecificDeployStrategies[$package->getName()])) {
                $strategyName = $moduleSpecificDeployStrategies[$package->getName()];
            }
        }

        if (!isset(static::$strategies[$strategyName])) {
            $className = static::$strategies['symlink'];
        } else {
            $className = static::$strategies[$strategyName];
        }

        if ($className === static::$strategies['none']) {
            $instance = new $className;
        } else {
            $instance = new $className(new FileSystem);
        }

        return $instance;
    }
}
