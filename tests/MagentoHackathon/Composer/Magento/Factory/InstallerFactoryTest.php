<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use PHPUnit_Framework_TestCase;

/**
 * Class InstallerFactoryTest
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testInstanceIsReturned()
    {
        $factory = new InstallerFactory;
        $instance = $factory->make(new ProjectConfig([], []), new EventManager);

        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Installer\Installer', $instance);
    }
}
