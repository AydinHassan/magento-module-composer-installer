<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * Class InstallStrategyFactoryTest
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallStrategyFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider strategyProvider
     * @param string $strategy
     * @param string $expectedClass
     */
    public function testCorrectDeployStrategyIsReturned($strategy, $expectedClass)
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array('magento-deploystrategy' => $strategy,), array());
        $factory = new InstallStrategyFactory($config);
        $instance = $factory->make($package);
        $this->assertInstanceOf($expectedClass, $instance);
    }

    /**
     * @return array
     */
    public function strategyProvider()
    {
        return array(
            array('copy',    '\MagentoHackathon\Composer\Magento\InstallStrategy\Copy'),
            array('symlink', '\MagentoHackathon\Composer\Magento\InstallStrategy\Symlink'),
            array('link',    '\MagentoHackathon\Composer\Magento\InstallStrategy\Link'),
            array('none',    '\MagentoHackathon\Composer\Magento\InstallStrategy\None'),
        );
    }

    public function testSymlinkStrategyIsUsedIfConfiguredStrategyNotFound()
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array('magento-deploystrategy' => 'lolnotarealstrategy',), array());
        $factory = new InstallStrategyFactory($config);

        $instance = $factory->make($package);
        $this->assertInstanceOf('\MagentoHackathon\Composer\Magento\InstallStrategy\Symlink', $instance);
    }

    public function testIndividualOverrideTakesPrecedence()
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array(
            'magento-deploystrategy' => 'symlink',
            'magento-deploystrategy-overwrite' => array('some/package' => 'none'),
        ), array());

        $factory = new InstallStrategyFactory($config);

        $instance = $factory->make($package);
        $this->assertInstanceOf('\MagentoHackathon\Composer\Magento\InstallStrategy\None', $instance);
    }

    /**
     * @dataProvider strategyProvider
     * @param string $strategy
     */
    public function testDetermineStrategy($strategy)
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array('magento-deploystrategy' => $strategy,), array());
        $factory = new InstallStrategyFactory($config);
        $name = $factory->determineStrategy($package);
        $this->assertSame($strategy, $name);
    }
}

/**
 * Override realpath function so we can force it to work with vfsStream
 *
 * @param string $path
 * @return string
 */
function realpath($path)
{
    return $path;
}
