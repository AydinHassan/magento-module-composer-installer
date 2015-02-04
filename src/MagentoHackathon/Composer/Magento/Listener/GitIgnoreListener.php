<?php

namespace MagentoHackathon\Composer\Magento\Listener;

use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Event\PackagePostInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;
use MagentoHackathon\Composer\Magento\GitIgnore;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;

/**
 * Class GitIgnoreListener
 * @package MagentoHackathon\Composer\Magento\Listener
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnoreListener
{

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    /**
     * @param GitIgnore $gitIgnore
     */
    public function __construct(GitIgnore $gitIgnore)
    {
        $this->gitIgnore = $gitIgnore;
    }

    /**
     * Add any files which were installed to the .gitignore
     *
     * @param PackagePostInstallEvent $e
     */
    public function addNewInstalledFiles(PackagePostInstallEvent $e)
    {
        $files = $this->processMappings($e->getMappings());
        $this->gitIgnore->addMultipleEntries($files);
        $this->gitIgnore->write();
    }

    /**
     * Remove any files which were removed to the .gitignore
     *
     * @param PackageUnInstallEvent $e
     */
    public function removeUnInstalledFiles(PackageUnInstallEvent $e)
    {
        $files = $this->processMappings($e->getMappings());
        $this->gitIgnore->removeMultipleEntries($files);
        $this->gitIgnore->write();
    }

    /**
     * @param MapCollection $maps
     * @return array
     */
    private function processMappings(MapCollection $maps)
    {
        return array_map(
            function (Map $map) {
                return sprintf('/%s', $map->getDestination());
            },
            $maps->all()
        );
    }
}
