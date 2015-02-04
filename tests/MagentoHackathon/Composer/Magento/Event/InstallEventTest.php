<?php

namespace MagentoHackathon\Composer\Magento\Event;

use ArrayObject;
use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
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
        $packages   = new ArrayObject([new Package('some/package', '1.0.0', 'some/package')]);
        $event      = new InstallEvent('pre-install', $packages);

        $this->assertEquals('pre-install', $event->getName());
        $this->assertSame($packages, $event->getPackages());
    }
}
