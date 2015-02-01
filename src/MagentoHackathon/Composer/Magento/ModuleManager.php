<?php

namespace MagentoHackathon\Composer\Magento;

use ArrayObject;
use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\InstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackagePostInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackagePreInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Installer\InstallerInterface;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageRepositoryInterface;
use MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategyInterface;

/**
 * Class ModuleManager
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ModuleManager
{
    /**
     * @var InstalledPackageRepositoryInterface
     */
    protected $installedPackageRepository;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var UnInstallStrategyInterface
     */
    protected $unInstallStrategy;

    /**
     * @var InstallerInterface
     */
    protected $installer;

    /**
     * @var InstallStrategyFactory
     */
    protected $installStrategyFactory;

    /**
     * @param InstalledPackageRepositoryInterface $installedRepository
     * @param EventManager $eventManager
     * @param ProjectConfig $config
     * @param UnInstallStrategyInterface $unInstallStrategy
     * @param InstallerInterface $installer
     * @param InstallStrategyFactory $installStrategyFactory
     */
    public function __construct(
        InstalledPackageRepositoryInterface $installedRepository,
        EventManager $eventManager,
        ProjectConfig $config,
        UnInstallStrategyInterface $unInstallStrategy,
        InstallerInterface $installer,
        InstallStrategyFactory $installStrategyFactory
    ) {
        $this->installedPackageRepository   = $installedRepository;
        $this->eventManager                 = $eventManager;
        $this->config                       = $config;
        $this->unInstallStrategy            = $unInstallStrategy;
        $this->installer                    = $installer;
        $this->installStrategyFactory = $installStrategyFactory;
    }

    /**
     * @param array $currentComposerInstalledPackages
     * @return array
     */
    public function updateInstalledPackages(array $currentComposerInstalledPackages)
    {
        $packagesToRemove = $this->getRemoves(
            $currentComposerInstalledPackages,
            $this->installedPackageRepository->findAll()
        );

        $packagesToInstall = $this->getInstalls(
            $currentComposerInstalledPackages
        );

        $this->doRemoves($packagesToRemove);
        $this->doInstalls($packagesToInstall);
    }

    /**
     * @param ArrayObject $packagesToRemove
     */
    public function doRemoves(ArrayObject $packagesToRemove)
    {
        foreach ($packagesToRemove as $package) {
            $mappings = $package->getMappings();

            $this->eventManager->dispatch(
                new PackageUnInstallEvent('pre-package-uninstall', $package, $mappings)
            );

            $this->unInstallStrategy->unInstall($mappings);
            $this->installedPackageRepository->remove($package);

            $this->eventManager->dispatch(
                new PackageUnInstallEvent('post-package-uninstall', $package, $mappings)
            );
        }
    }

    /**
     * @param ArrayObject $packagesToInstall
     */
    protected function doInstalls(ArrayObject $packagesToInstall)
    {
        $this->eventManager->dispatch(new InstallEvent('pre-install', $packagesToInstall));

        foreach ($packagesToInstall as $package) {
            $this->eventManager->dispatch(new PackagePreInstallEvent($package));

            $mappings = $this->installer->install($package, $this->getPackageSourceDirectory($package));

            $installedPackaged = new InstalledPackage(
                $package->getName(),
                $package->getVersion(),
                $mappings
            );

            $this->installedPackageRepository->add($installedPackaged);

            $this->eventManager->dispatch(new PackagePostInstallEvent($package, $installedPackaged));
        }

        $this->eventManager->dispatch(new InstallEvent('post-install', $packagesToInstall));
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @param InstalledPackage[] $magentoInstalledPackages
     * @return ArrayObject
     */
    protected function getRemoves(array $currentComposerInstalledPackages, array $magentoInstalledPackages)
    {
        //make the package names as the array keys
        $currentComposerInstalledPackages = array_combine(
            array_map(
                function (PackageInterface $package) {
                    return $package->getName();
                },
                $currentComposerInstalledPackages
            ),
            $currentComposerInstalledPackages
        );

        return new ArrayObject(
            array_filter(
                $magentoInstalledPackages,
                function (InstalledPackage $package) use ($currentComposerInstalledPackages) {
                    if (!isset($currentComposerInstalledPackages[$package->getName()])) {
                        return true;
                    }

                    $composerPackage = $currentComposerInstalledPackages[$package->getName()];
                    return $package->getUniqueName() !== $composerPackage->getUniqueName();
                }
            )
        );
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @return ArrayObject
     */
    protected function getInstalls(array $currentComposerInstalledPackages)
    {
        $repo = $this->installedPackageRepository;
        return new ArrayObject(
            array_filter($currentComposerInstalledPackages, function (PackageInterface $package) use ($repo) {
                return !$repo->has($package->getName(), $package->getVersion());
            })
        );
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    private function getPackageSourceDirectory(PackageInterface $package)
    {
        $path = sprintf("%s/%s", $this->config->getVendorDir(), $package->getPrettyName());
        $targetDir = $package->getTargetDir();

        if ($targetDir) {
            $path = sprintf("%s/%s", $path, $targetDir);
        }

        return $path;
    }
}
