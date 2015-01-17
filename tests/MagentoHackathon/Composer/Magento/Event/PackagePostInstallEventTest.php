<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
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
        $package    = new Package('some/package', '1.0.0', 'some/package');
        $event      = new PackagePostInstallEvent($package, array('file1', 'file2'));

        $this->assertEquals('package-post-install', $event->getName());
        $this->assertSame($package, $event->getPackage());
        $this->assertEquals(array('file1', 'file2'), $event->getInstalledFiles());
    }
}
