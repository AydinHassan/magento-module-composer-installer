<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\InstalledPackage;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use PHPUnit_Framework_TestCase;

/**
 * Class PackagePostInstallEventTest
 * @package MagentoHackathon\Composer\Magento\Event
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackagePostInstallEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $map        = new Map('source', 'destination', '/tmp/', '/tmp/');
        $collection = new MapCollection(array($map));
        $installedP = new InstalledPackage('some/package', '1.0.0', $collection);

        $package    = new Package('some/package', '1.0.0', 'some/package');
        $event      = new PackagePostInstallEvent($package, $installedP);

        $this->assertEquals('package-post-install', $event->getName());
        $this->assertSame($package, $event->getPackage());
        $this->assertEquals($collection, $event->getMappings());
    }
}
