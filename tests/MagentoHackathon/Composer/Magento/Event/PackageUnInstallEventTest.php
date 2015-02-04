<?php

namespace MagentoHackathon\Composer\Magento\Event;

use MagentoHackathon\Composer\Magento\InstalledPackage;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use PHPUnit_Framework_TestCase;

/**
 * Class PackageUnInstallEventTest
 * @package MagentoHackathon\Composer\Magento\Event
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackageUnInstallEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {

        $maps = new MapCollection(array(
            new Map('file1', 'file1', '/', '/'),
            new Map('file2', 'file2', '/', '/'),
        ));
        $package    = new InstalledPackage('some/package', '1.0.0', $maps);
        $event      = new PackageUnInstallEvent('package-uninstall-event', $package);

        $this->assertSame($package, $event->getPackage());
        $this->assertEquals($maps, $event->getMappings());
    }
}
