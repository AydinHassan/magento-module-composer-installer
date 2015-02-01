<?php

namespace MagentoHackathon\Composer\Magento\Listener;

use ArrayObject;
use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\InstallEvent;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use PHPUnit_Framework_TestCase;

/**
 * Class PackagePrioritySortListenerTest
 * @package MagentoHackathon\Composer\Magento\Listener
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackagePrioritySortListenerTest extends PHPUnit_Framework_TestCase
{

    public function testSortWithNoConfigChanges()
    {

        $package1 = new Package("vendor/package1", "1.1.0", "vendor/package1");
        $package2 = new Package("vendor/package2", "1.1.0", "vendor/package2");
        $packages = new ArrayObject(array($package1, $package2));

        $config = new ProjectConfig(array(), array());
        $installStrategyFactory = new InstallStrategyFactory($config);

        $listener = new PackagePrioritySortListener($installStrategyFactory, $config);
        $listener->__invoke(new InstallEvent('pre-install', $packages));

        $this->assertEquals(
            array(
                $package1,
                $package2
            ),
            $packages->getArrayCopy()
        );
    }

    public function testSortWithDifferentInstallStrategies()
    {

        $package1 = new Package("vendor/package1", "1.1.0", "vendor/package1");
        $package2 = new Package("vendor/package2", "1.1.0", "vendor/package2");
        $packages = new ArrayObject(array($package1, $package2));

        $config = new ProjectConfig(
            array(
                'magento-deploystrategy-overwrite' => array(
                    'vendor/package1' => 'symlink',
                    'vendor/package2' => 'copy',
                ),
            ),
            array()
        );
        $installStrategyFactory = new InstallStrategyFactory($config);

        $listener = new PackagePrioritySortListener($installStrategyFactory, $config);
        $listener->__invoke(new InstallEvent('pre-install', $packages));

        $this->assertEquals(
            array(
                $package2,
                $package1,
            ),
            array_values($packages->getArrayCopy())
        );
    }

    public function testSortWithUserPriorities()
    {

        $package1 = new Package("vendor/package1", "1.1.0", "vendor/package1");
        $package2 = new Package("vendor/package2", "1.1.0", "vendor/package2");
        $package3 = new Package("vendor/package3", "1.1.0", "vendor/package3");
        $packages = new ArrayObject(array($package1, $package2, $package3));

        $config = new ProjectConfig(
            array(
                'magento-deploy-sort-priority' => array(
                    'vendor/package1' => 200,
                    'vendor/package2' => 400,
                    'vendor/package3' => 1000,
                ),
            ),
            array()
        );
        $installStrategyFactory = new InstallStrategyFactory($config);

        $listener = new PackagePrioritySortListener($installStrategyFactory, $config);
        $listener->__invoke(new InstallEvent('pre-install', $packages));

        $this->assertEquals(
            array(
                $package3,
                $package2,
                $package1,
            ),
            array_values($packages->getArrayCopy())
        );
    }
}
