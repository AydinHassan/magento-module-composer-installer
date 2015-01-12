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
use MagentoHackathon\Composer\Magento\Factory\ParserFactory;
use MagentoHackathon\Composer\Magento\Factory\PathTranslationParserFactory;
use MagentoHackathon\Composer\Magento\Installer\MagentoInstallerAbstract;
use MagentoHackathon\Composer\Magento\Installer\ModuleInstaller;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

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
     * @var DeployManager
     */
    protected $deployManager;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var EntryFactory
     */
    protected $entryFactory;

    /**
     * init the DeployManager
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    protected function initDeployManager(Composer $composer, IOInterface $io, EventManager $eventManager)
    {
        $this->deployManager = new DeployManager($eventManager);
        $this->deployManager->setSortPriority($this->getSortPriority($composer));

        if ($this->config->hasAutoAppendGitignore()) {
            $gitIgnoreLocation = sprintf('%s/.gitignore', $this->config->getMagentoRootDir());
            $eventManager->listen('post-package-deploy', new GitIgnoreListener(new GitIgnore($gitIgnoreLocation)));
        }

        if ($this->io->isDebug()) {
            $eventManager->listen('pre-package-deploy', function(PackageDeployEvent $event) use ($io) {
                $io->write('Start magento deploy for ' . $event->getDeployEntry()->getPackageName());
            });
        }
    }

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
        $this->io = $io;
        $this->composer = $composer;

        $this->filesystem = new Filesystem();
        $this->config = new ProjectConfig($composer->getPackage()->getExtra(), $composer->getConfig()->all());

        $this->initDeployManager($composer, $io, $this->getEventManager());

        $this->writeDebug('activate magento plugin');
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

        $magentoModules = array_map(function(PackageInterface $package) {
            return $package->getType() === static::PACKAGE_TYPE;
        }, $this->composer->getRepositoryManager()->getLocalRepository()->getPackages());




        $this->writeDebug('iterate over packages to find missing ones');
        $addedPackageNames = array();
        foreach ($this->deployManager->getEntries() as $entry) {
            $addedPackageNames[$entry->getPackageName()] = $entry->getPackageName();
        }
        /** @var PackageInterface[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        
        foreach ($packages as $package) {
            if ($package->getType() == 'magento-module' && !isset($addedPackageNames[$package->getName()])) {
                $this->writeDebug('add missing package '.$package->getName());
                //$entry = $this->entryFactory->make($package, $this->getPackageInstallPath($package));
                //$this->deployManager->addPackage($entry);
            }
        }

        $this->writeDebug('start magento module deploy via deployManager');

        $this->writeDebug('start magento deploy via deployManager');

        $this->writeDebug('start magento module deploy via deployManager');
        $this->deployManager->doDeploy();
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

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return new EventManager;
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function getPackageInstallPath(PackageInterface $package)
    {
        $vendorDir = realpath(rtrim($this->composer->getConfig()->get('vendor-dir'), '/'));
        return sprintf('%s/%s', $vendorDir, $package->getPrettyName());
    }
}
