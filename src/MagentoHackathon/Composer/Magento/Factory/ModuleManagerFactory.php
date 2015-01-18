<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackagePreInstallEvent;
use MagentoHackathon\Composer\Magento\GitIgnore;
use MagentoHackathon\Composer\Magento\GitIgnoreListener;
use MagentoHackathon\Composer\Magento\InstalledPackageDumper;
use MagentoHackathon\Composer\Magento\ModuleManager;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageFileSystemRepository;
use MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategy;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class ModuleManagerFactory
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ModuleManagerFactory
{
    /**
     * @param ProjectConfig $config
     * @param EventManager  $eventManager
     * @param IOInterface   $io
     *
     * @return ModuleManager
     */
    public function make(ProjectConfig $config, EventManager $eventManager, IOInterface $io)
    {

        if ($config->hasAutoAppendGitignore()) {
            $this->addGitIgnoreListener($eventManager, $config);
        }

        if ($io->isDebug()) {
            $this->addDebugListener($eventManager, $io);
        }

        $installerFactory = new InstallerFactory;
        return new ModuleManager(
            new InstalledPackageFileSystemRepository('some_path.json', new InstalledPackageDumper),
            $eventManager,
            $config,
            new UnInstallStrategy(new FileSystem, $config->getMagentoRootDir()),
            $installerFactory->make($config, $eventManager),
            new InstallStrategyFactory($config)
        );
    }

    /**
     * @param EventManager $eventManager
     * @param ProjectConfig $config
     */
    protected function addGitIgnoreListener(EventManager $eventManager, ProjectConfig $config)
    {
        $gitIgnoreLocation  = sprintf('%s/.gitignore', $config->getMagentoRootDir());
        $gitIgnore          = new GitIgnoreListener(new GitIgnore($gitIgnoreLocation));

        $eventManager->listen('package-post-install', array($gitIgnore, 'addNewInstalledFiles'));
        $eventManager->listen('package-post-uninstall', array($gitIgnore, 'removeUnInstalledFiles'));
    }

    /**
     * @param EventManager $eventManager
     * @param IOInterface  $io
     */
    protected function addDebugListener(EventManager $eventManager, IOInterface $io)
    {
        $eventManager->listen('package-pre-install', function (PackagePreInstallEvent $event) use ($io) {
            $io->write('Start magento deploy for ' . $event->getPackage()->getName());
        });
    }
}
