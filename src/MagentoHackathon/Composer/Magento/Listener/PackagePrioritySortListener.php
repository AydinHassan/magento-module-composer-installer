<?php

namespace MagentoHackathon\Composer\Magento\Listener;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\InstallEvent;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class PackagePrioritySortListener
 * @package MagentoHackathon\Composer\Magento\Listener
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackagePrioritySortListener
{
    /**
     * @var InstallStrategyFactory
     */
    protected $installStrategyFactory;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @param InstallStrategyFactory $installStrategyFactory
     * @param ProjectConfig          $config
     */
    public function __construct(
        InstallStrategyFactory $installStrategyFactory,
        ProjectConfig $config
    ) {

        $this->installStrategyFactory = $installStrategyFactory;
        $this->config = $config;
    }

    /**
     * @param InstallEvent $event
     */
    public function __invoke(InstallEvent $event)
    {
        $packagesToInstall  = $event->getPackages();
        $userPriorities     = $this->config->getSortPriorities();
        $priorities         = [];
        foreach ($packagesToInstall as $package) {
            /** @var PackageInterface $package */
            if (isset($userPriorities[$package->getName()])) {
                $priority = $userPriorities[$package->getName()];
            } else {
                $priority = $this->installStrategyFactory->getDefaultPriority($package);
            }

            $priorities[$package->getName()] = $priority;
        }

        $packagesToInstall->uasort(
            function (PackageInterface $a, PackageInterface $b) use ($priorities) {
                $aVal = $priorities[$a->getName()];
                $bVal = $priorities[$b->getName()];

                if ($aVal === $bVal) {
                    return 0;
                }
                return ($aVal > $bVal) ? -1 : 1;
            }
        );
    }
}
