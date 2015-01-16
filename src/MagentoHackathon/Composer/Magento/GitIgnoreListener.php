<?php

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Event\PackagePostInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;

/**
 * Class GitIgnoreListener
 * @package MagentoHackathon\Composer\Magento
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
        $files = $e->getInstalledFiles();
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
        $files = $e->getUnInstalledFiles();
        $this->gitIgnore->removeMultipleEntries($files);
        $this->gitIgnore->write();
    }
}
