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

class Plugin2 implements PluginInterface, EventSubscriberInterface
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
     * get Sort Priority from extra Config
     *
     * @param \Composer\Composer $composer
     *
     * @return array
     */
    private function getSortPriority(Composer $composer)
    {
        $extra = $composer->getPackage()->getExtra();

        return isset($extra[ProjectConfig::SORT_PRIORITY_KEY])
            ? $extra[ProjectConfig::SORT_PRIORITY_KEY]
            : array();
    }

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
        $this->config           = new ProjectConfig($composer->getPackage()->getExtra(), $composer->getConfig()->all());
        $this->eventManager     = new EventManager;
        $moduleManagerFactory   = new ModuleManagerFactory;
        $this->moduleManager    = $moduleManagerFactory->make($this->config, $this->eventManager, $io);

        $this->writeDebug('Activate magento plugin');
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
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
     * event listener is named this way, as it listens for events leading to changed code files
     *
     * @param CommandEvent $event
     */
    public function onNewCodeEvent(CommandEvent $event)
    {
        $magentoModules = array_map(function (PackageInterface $package) {
            return $package->getType() === static::PACKAGE_TYPE;
        }, $this->composer->getRepositoryManager()->getLocalRepository()->getPackages());

        $this->moduleManager->updateInstalledPackages($magentoModules);
    }

    /**
     * print Debug Message
     *
     * @param $message
     */
    private function writeDebug($message)
    {
        if ($this->io->isDebug()) {
            $this->io->write($message);
        }
    }
}
