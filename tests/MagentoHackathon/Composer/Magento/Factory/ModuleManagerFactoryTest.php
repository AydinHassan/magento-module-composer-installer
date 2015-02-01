<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\IO\ConsoleIO;
use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackagePreInstallEvent;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class ModuleManagerFactoryTest
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ModuleManagerFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testFactoryReturnsInstance()
    {
        $factory        = new ModuleManagerFactory;
        $config         = new ProjectConfig(array(), array('vendor-dir' => 'vendor'));
        $eventManager   = $this->getMock('MagentoHackathon\Composer\Magento\Event\EventManager');
        $io             = new ConsoleIO(new ArrayInput(array()), new ConsoleOutput(), new HelperSet());

        $eventManager
            ->expects($this->at(0))
            ->method('listen')
            ->with(
                'pre-install',
                $this->isInstanceOf('MagentoHackathon\Composer\Magento\Listener\CheckAndCreateMagentoRootDirListener')
            );

        $eventManager
            ->expects($this->at(1))
            ->method('listen')
            ->with(
                'pre-install',
                $this->isInstanceOf('MagentoHackathon\Composer\Magento\Listener\PackagePrioritySortListener')
            );

        $instance = $factory->make($config, $eventManager, $io);
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\ModuleManager', $instance);
    }

    public function testDebugPrinterIsAddedIfDebugMode()
    {
        $factory        = new ModuleManagerFactory;
        $config         = new ProjectConfig(array(), array('vendor-dir' => 'vendor'));
        $eventManager   = $this->getMock('MagentoHackathon\Composer\Magento\Event\EventManager');
        $io             = new ConsoleIO(
            new ArrayInput(array()),
            new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG),
            new HelperSet()
        );

        $eventManager
            ->expects($this->at(0))
            ->method('listen')
            ->with('package-pre-install', $this->isInstanceOf('Closure'));

        $eventManager
            ->expects($this->at(1))
            ->method('listen')
            ->with(
                'pre-install',
                $this->isInstanceOf('MagentoHackathon\Composer\Magento\Listener\CheckAndCreateMagentoRootDirListener')
            );

        $eventManager
            ->expects($this->at(2))
            ->method('listen')
            ->with(
                'pre-install',
                $this->isInstanceOf('MagentoHackathon\Composer\Magento\Listener\PackagePrioritySortListener')
            );

        $factory->make($config, $eventManager, $io);
    }

    public function testGitIgnoreListenerIsAddedIfConfigPresent()
    {
        $factory        = new ModuleManagerFactory;
        $config         = new ProjectConfig(array('auto-append-gitignore' => true), array('vendor-dir' => 'vendor'));
        $eventManager   = $this->getMock('MagentoHackathon\Composer\Magento\Event\EventManager');
        $io             = new ConsoleIO(new ArrayInput(array()), new ConsoleOutput(), new HelperSet());

        $eventManager
            ->expects($this->at(0))
            ->method('listen')
            ->with('package-post-install', $this->isType('array'));

        $eventManager
            ->expects($this->at(1))
            ->method('listen')
            ->with('package-post-uninstall', $this->isType('array'));

        $eventManager
            ->expects($this->at(2))
            ->method('listen')
            ->with(
                'pre-install',
                $this->isInstanceOf('MagentoHackathon\Composer\Magento\Listener\CheckAndCreateMagentoRootDirListener')
            );

        $eventManager
            ->expects($this->at(3))
            ->method('listen')
            ->with(
                'pre-install',
                $this->isInstanceOf('MagentoHackathon\Composer\Magento\Listener\PackagePrioritySortListener')
            );

        $factory->make($config, $eventManager, $io);
    }

    public function testDebugListenerCallback()
    {
        $factory        = new ModuleManagerFactory;
        $config         = new ProjectConfig(array('auto-append-gitignore' => true), array('vendor-dir' => 'vendor'));
        $eventManager   = new EventManager;
        $io             = $this->getMock('Composer\IO\IOInterface');

        $io->expects($this->any())
            ->method('isDebug')
            ->will($this->returnValue(true));

        $factory->make($config, $eventManager, $io);

        $io->expects($this->once())
            ->method('write')
            ->with('Start magento deploy for some/package');

        $eventManager->dispatch(new PackagePreInstallEvent(new Package('some/package', '1.0.0', 'some/package')));
    }
}
