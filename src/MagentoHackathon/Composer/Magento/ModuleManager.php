<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackagePostInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackagePreInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;
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
     * @param InstalledPackageRepositoryInterface $installedRepository
     * @param EventManager $eventManager
     * @param ProjectConfig $config
     * @param UnInstallStrategyInterface $unInstallStrategy
     * @param InstallerInterface $installer
     */
    public function __construct(
        InstalledPackageRepositoryInterface $installedRepository,
        EventManager $eventManager,
        ProjectConfig $config,
        UnInstallStrategyInterface $unInstallStrategy,
        InstallerInterface $installer
    ) {
        $this->installedPackageRepository   = $installedRepository;
        $this->eventManager                 = $eventManager;
        $this->config                       = $config;
        $this->unInstallStrategy            = $unInstallStrategy;
        $this->installer                    = $installer;
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
     * @param InstalledPackage[] $packagesToRemove
     */
    public function doRemoves(array $packagesToRemove)
    {
        foreach ($packagesToRemove as $package) {
            $unInstalledFiles = $package->getInstalledFiles();

            $this->eventManager->dispatch(new PackageUnInstallEvent('pre-package-uninstall', $package, $unInstalledFiles));

            $this->unInstallStrategy->unInstall($unInstalledFiles);
            $this->installedPackageRepository->remove($package);

            $this->eventManager->dispatch(new PackageUnInstallEvent('post-package-uninstall', $package, $unInstalledFiles));
        }
    }

    /**
     * @param PackageInterface[] $packagesToInstall
     */
    protected function doInstalls(array $packagesToInstall)
    {
        foreach ($packagesToInstall as $package) {
            $this->eventManager->dispatch(new PackagePreInstallEvent($package));

            $mappings = $this->installer->install($package, $this->getPackageSourceDirectory($package));

            $this->installedPackageRepository->add(new InstalledPackage(
                $package->getName(),
                $package->getVersion(),
                $mappings->getAllDestinations()
            ));

            $this->eventManager->dispatch(new PackagePostInstallEvent($package, $mappings->getAllDestinations()));
        }
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @param InstalledPackage[] $magentoInstalledPackages
     * @return InstalledPackage[]
     */
    public function getRemoves(array $currentComposerInstalledPackages, array $magentoInstalledPackages)
    {
        //make the package names as the array keys
        $currentComposerInstalledPackages = array_combine(
            array_map(
                function (PackageInterface $package) {
                    return $package->getPrettyName();
                },
                $currentComposerInstalledPackages
            ),
            $currentComposerInstalledPackages
        );

        return array_filter(
            $magentoInstalledPackages,
            function (InstalledPackage $package) use ($currentComposerInstalledPackages) {
                if (!isset($currentComposerInstalledPackages[$package->getName()])) {
                    return true;
                }

                $composerPackage = $currentComposerInstalledPackages[$package->getName()];
                return $package->getUniqueName() !== $composerPackage->getUniqueName();
            }
        );
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @return PackageInterface[]
     */
    public function getInstalls(array $currentComposerInstalledPackages)
    {
        $repo = $this->installedPackageRepository;
        return array_filter($currentComposerInstalledPackages, function (PackageInterface $package) use ($repo) {
            return !$repo->has($package->getName(), $package->getVersion());
        });
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
