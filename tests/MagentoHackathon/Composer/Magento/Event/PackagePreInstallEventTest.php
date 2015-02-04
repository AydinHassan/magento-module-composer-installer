<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use PHPUnit_Framework_TestCase;

/**
 * Class PackagePreInstallEventTest
 * @package MagentoHackathon\Composer\Magento\Event
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackagePreInstallEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $package    = new Package('some/package', '1.0.0', 'some/package');
        $event      = new PackagePreInstallEvent($package);

        $this->assertEquals('package-pre-install', $event->getName());
        $this->assertSame($package, $event->getPackage());
    }
}
