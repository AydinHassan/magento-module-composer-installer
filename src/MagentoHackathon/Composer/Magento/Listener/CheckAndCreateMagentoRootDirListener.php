<?php

namespace MagentoHackathon\Composer\Magento\Listener;

use MagentoHackathon\Composer\Magento\Event\InstallEvent;

/**
 * Class CheckAndCreateMagentoRootDirListener
 * @package MagentoHackathon\Composer\Magento\Listener
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class CheckAndCreateMagentoRootDirListener
{

    /**
     * @var string
     */
    protected $magentoRootDir;

    /**
     * @param string $magentoRootDir
     */
    public function __construct($magentoRootDir)
    {
        $this->magentoRootDir = $magentoRootDir;
    }

    /**
     * @param InstallEvent $event
     */
    public function __invoke(InstallEvent $event)
    {
        if (!file_exists($this->magentoRootDir)) {
            mkdir($this->magentoRootDir);
        }
    }
}
