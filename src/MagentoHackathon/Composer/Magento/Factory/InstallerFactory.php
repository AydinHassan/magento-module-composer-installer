<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Installer\GlobResolver;
use MagentoHackathon\Composer\Magento\Installer\Installer;
use MagentoHackathon\Composer\Magento\Installer\TargetFilter;
use MagentoHackathon\Composer\Magento\Parser\Parser;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class InstallerFactory
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallerFactory
{
    /**
     * @param ProjectConfig $config
     * @param EventManager  $eventManager
     *
     * @return Installer
     */
    public function make(ProjectConfig $config, EventManager $eventManager)
    {
        $installStrategyFactory = new InstallStrategyFactory($config);
        $fileSystem             = new FileSystem;
        $globResolver           = new GlobResolver;
        $targetFilter           = new TargetFilter($config->getMagentoDeployIgnore());
        $parser                 = new Parser(new ParserFactory($config));

        return new Installer(
            $installStrategyFactory,
            $fileSystem,
            $config,
            $globResolver,
            $targetFilter,
            $parser,
            $eventManager
        );
    }
}
