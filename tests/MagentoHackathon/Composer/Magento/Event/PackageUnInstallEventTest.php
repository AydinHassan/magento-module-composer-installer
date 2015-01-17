<?php

namespace MagentoHackathon\Composer\Magento\Event;

use MagentoHackathon\Composer\Magento\InstalledPackage;
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
        $files      = array('file1');
        $package    = new InstalledPackage('some/package', '1.0.0', $files);
        $event      = new PackageUnInstallEvent('package-uninstall-event', $package);

        $this->assertSame($package, $event->getPackage());
        $this->assertEquals($files, $event->getUnInstalledFiles());
    }
}
