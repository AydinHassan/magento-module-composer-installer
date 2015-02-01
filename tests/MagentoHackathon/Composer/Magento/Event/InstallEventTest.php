<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\InstalledPackage;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use PHPUnit_Framework_TestCase;

/**
 * Class InstallEventTest
 * @package MagentoHackathon\Composer\Magento\Event
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $packages   = array(new Package('some/package', '1.0.0', 'some/package'));
        $event      = new InstallEvent('pre-install', $packages);

        $this->assertEquals('pre-install', $event->getName());
        $this->assertSame($packages, $event->getPackages());
    }
}
