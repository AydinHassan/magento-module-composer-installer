<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Config;
use Composer\Installer;
use Composer\Script\CommandEvent;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Factory\DeploystrategyFactory;
use MagentoHackathon\Composer\Magento\Factory\EntryFactory;
use MagentoHackathon\Composer\Magento\Factory\ModuleManagerFactory;
use MagentoHackathon\Composer\Magento\Factory\ParserFactory;
use MagentoHackathon\Composer\Magento\Factory\PathTranslationParserFactory;
use MagentoHackathon\Composer\Magento\Installer\MagentoInstallerAbstract;
use MagentoHackathon\Composer\Magento\Installer\ModuleInstaller;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class Plugin
 * @package MagentoHackathon\Composer\Magento
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * The type of packages this plugin supports
     */
    const PACKAGE_TYPE = 'magento-module';

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io               = $io;
        $this->composer         = $composer;
        $composerConfig         = $composer->getConfig()->all();
        $this->config           = new ProjectConfig($composer->getPackage()->getExtra(), $composerConfig['config']);
        $this->eventManager     = new EventManager;
        $moduleManagerFactory   = new ModuleManagerFactory;
        $this->moduleManager    = $moduleManagerFactory->make($this->config, $this->eventManager, $io);

        if ($io->isDebug()) {
            $io->write('Activate Magento plugin');
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => array(
                array('onNewCodeEvent', 0),
            ),
            ScriptEvents::POST_UPDATE_CMD  => array(
                array('onNewCodeEvent', 0),
            ),
        );
    }

    /**
     * This event is triggered after installing or updating composer
     *
     * @param CommandEvent $event
     */
    public function onNewCodeEvent(CommandEvent $event)
    {
        $magentoModules = array_filter(
            $this->composer->getRepositoryManager()->getLocalRepository()->getPackages(),
            function (PackageInterface $package) {
                return $package->getType() === static::PACKAGE_TYPE;
            }
        );

        $this->moduleManager->updateInstalledPackages($magentoModules);
    }
}
