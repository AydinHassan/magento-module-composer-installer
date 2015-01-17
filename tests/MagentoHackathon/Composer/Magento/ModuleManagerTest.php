<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageFileSystemRepository;
use org\bovigo\vfs\vfsStream;

/**
 * Class ModuleManagerTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ModuleManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $moduleManager;
    protected $installedPackageRepository;
    protected $unInstallStrategy;
    protected $installer;

    public function setUp()
    {
        vfsStream::setup('root');
        $this->installedPackageRepository = new InstalledPackageFileSystemRepository(
            vfsStream::url('root/installed.json'),
            new InstalledPackageDumper()
        );

        $config = new ProjectConfig(array(), array('vendor-dir' => 'vendor'));
        $this->unInstallStrategy =
            $this->getMock('MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategyInterface');

        $this->installer = $this->getMock('MagentoHackathon\Composer\Magento\Installer\InstallerInterface');
        $this->moduleManager = new ModuleManager(
            $this->installedPackageRepository,
            new EventManager,
            $config,
            $this->unInstallStrategy,
            $this->installer
        );
    }

    public function testPackagesRemovedFromComposerAreMarkedForUninstall()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.0.0", "vendor/package1")
        );

        $installedMagentoPackages = array(
            new InstalledPackage("vendor/package1", "1.0.0", array()),
            new InstalledPackage("vendor/package2", "1.0.0", array('file1')),
        );

        $this->installedPackageRepository->add($installedMagentoPackages[0]);
        $this->installedPackageRepository->add($installedMagentoPackages[1]);

        $this->unInstallStrategy
            ->expects($this->once())
            ->method('unInstall')
            ->with($installedMagentoPackages[1]->getInstalledFiles());

        $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
    }

    public function testPackagesNotInstalledAreMarkedForInstall()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.0.0", "vendor/package1")
        );

        $this->installer
            ->expects($this->once())
            ->method('install')
            ->with($composerInstalledPackages[0])
            ->will($this->returnValue(new MapCollection(array())));

        $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
    }

    public function testUpdatedPackageIsMarkedForUninstallAndReInstall()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.1.0", "vendor/package1")
        );

        $installedMagentoPackages = array(
            new InstalledPackage("vendor/package1", "1.0.0", array()),
        );

        $this->installedPackageRepository->add($installedMagentoPackages[0]);

        $this->unInstallStrategy
            ->expects($this->once())
            ->method('unInstall')
            ->with($installedMagentoPackages[0]->getInstalledFiles());

        $this->installer
            ->expects($this->once())
            ->method('install')
            ->with($composerInstalledPackages[0])
            ->will($this->returnValue(new MapCollection(array())));

        $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
    }

    public function testMultipleInstallsAndUnInstalls()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.1.0", "vendor/package1"),
            new Package("vendor/package2", "1.1.0", "vendor/package2"),
        );

        $installedMagentoPackages = array(
            new InstalledPackage("vendor/package1", "1.0.0", array()),
            new InstalledPackage("vendor/package2", "1.0.0", array()),
        );

        $this->installedPackageRepository->add($installedMagentoPackages[0]);
        $this->installedPackageRepository->add($installedMagentoPackages[1]);

        $this->unInstallStrategy
            ->expects($this->at(0))
            ->method('unInstall')
            ->with($installedMagentoPackages[0]->getInstalledFiles());

        $this->unInstallStrategy
            ->expects($this->at(1))
            ->method('unInstall')
            ->with($installedMagentoPackages[1]->getInstalledFiles());

        $this->installer
            ->expects($this->at(0))
            ->method('install')
            ->with($composerInstalledPackages[0])
            ->will($this->returnValue(new MapCollection(array())));

        $this->installer
            ->expects($this->at(1))
            ->method('install')
            ->with($composerInstalledPackages[1])
            ->will($this->returnValue(new MapCollection(array())));

        $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
    }
}
