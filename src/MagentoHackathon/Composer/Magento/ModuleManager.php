<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Installer\Installer;
use MagentoHackathon\Composer\Magento\Map\Map;
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
     * @var Installer
     */
    protected $installer;

    /**
     * @param InstalledPackageRepositoryInterface $installedRepository
     * @param EventManager $eventManager
     * @param ProjectConfig $config
     * @param UnInstallStrategyInterface $unInstallStrategy
     * @param Installer $installer
     */
    public function __construct(
        InstalledPackageRepositoryInterface $installedRepository,
        EventManager $eventManager,
        ProjectConfig $config,
        UnInstallStrategyInterface $unInstallStrategy,
        Installer $installer
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

        $packagesToInstall = $this->getInstalls($currentComposerInstalledPackages);

        $this->doRemoves($packagesToRemove);
        //$this->doInstalls($packagesToInstall);

        return array(
            $packagesToRemove,
            $packagesToInstall
        );
    }

    /**
     * @param array $packagesToRemove
     */
    public function doRemoves(array $packagesToRemove)
    {
        foreach ($packagesToRemove as $remove) {
            //$this->eventManager->dispatch(new PackageUnInstallEvent('pre-package-uninstall', $remove));
            $this->unInstallStrategy->unInstall($remove->getInstalledFiles());
            //$this->eventManager->dispatch(new PackageUnInstallEvent('post-package-uninstall', $remove));
            $this->installedPackageRepository->remove($remove);
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

    /**
     * @param PackageInterface[] $packagesToInstall
     */
    protected function doInstalls(array $packagesToInstall)
    {
        foreach ($packagesToInstall as $package) {
            $mappings = $this->installer->install($package, $this->getPackageSourceDirectory($package));

            $installedFiles = array_map(
                function (Map $map) {
                    return $map->getAbsoluteDestination();
                },
                $mappings->all()
            );

            $this->installedPackageRepository->add(new InstalledPackage(
                $package->getName(),
                $package->getVersion(),
                $installedFiles
            ));
        }
    }
}
