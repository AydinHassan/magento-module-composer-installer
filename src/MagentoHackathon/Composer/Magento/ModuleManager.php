<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageRepositoryInterface;

class ModuleManager
{
    protected $installedPackageRepository;

    protected $eventManager;

    protected $packageRemover;

    protected $installStrategyFactory;

    public function __construct(
        InstalledPackageRepositoryInterface $installedRepository,
        EventManager $eventManager
        //UnInstallStrategy $unInstallStrategy,
        //InstallStrategyFactory $installStrategyFactory
    ) {
        $this->installedPackageRepository = $installedRepository;
        $this->eventManager = $eventManager;
        //$this->unInstallStrategy = $unInstallStrategy;
        //$this->installStrategyFactory = $installStrategyFactory;
    }

    /**
     * @param array $currentComposerInstalledPackages
     * @return array
     */
    public function updateInstalledPackages(array $currentComposerInstalledPackages)
    {
        $packagesToRemove   = $this->getRemoves($currentComposerInstalledPackages);
        $packagesToInstall  = $this->getInstalls($currentComposerInstalledPackages);

        foreach ($packagesToRemove as $remove) {
            $this->unInstallStrategy->unInstall($remove);
        }

        foreach ($packagesToInstall as $install) {
            $this->installStrategyFactory->make($install)->deploy();
        }

        return array(
            $packagesToRemove,
            $packagesToInstall
        );
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @return InstalledPackage[]
     */
    public function getRemoves(array $currentComposerInstalledPackages)
    {
        $packagesToRemove = array();
        foreach ($this->installedPackageRepository->findAll() as $key => $package) {
            if ($this->shouldPackageBeRemoved($package, $currentComposerInstalledPackages)) {
                $packagesToRemove[] = $package;
            }
        }
        return $packagesToRemove;
    }

    /**
     * @param InstalledPackage $package
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @return bool
     */
    private function shouldPackageBeRemoved(InstalledPackage $package, array $currentComposerInstalledPackages)
    {
        foreach ($currentComposerInstalledPackages as $installedPackage) {
            if ($this->isPackageIdentical($package, $installedPackage)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param InstalledPackage $installedPackage
     * @param PackageInterface $composerPackage
     * @return bool
     */
    private function isPackageIdentical(InstalledPackage $installedPackage, PackageInterface $composerPackage)
    {
        return $installedPackage->getName() === $composerPackage->getName()
        && $installedPackage->getVersion() === $composerPackage->getVersion();
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @return PackageInterface[]
     */
    public function getInstalls(array $currentComposerInstalledPackages)
    {
        $packagesToInstall = array();
        foreach ($currentComposerInstalledPackages as $package) {
            if (!$this->installedPackageRepository->has($package->getName(), $package->getVersion())) {
                $packagesToInstall[] = $package;
            }
        }
        return $packagesToInstall;
    }
}
