<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Factory\InstallerFactory;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Composer\Util\Filesystem;

/**
 * Class InstallerTest
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class InstallerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Installer
     */
    protected $installer;

    protected $config;
    protected $root;
    protected $projectLocation;
    protected $testPackageLocation;
    protected $factory;
    protected $fileSystem;

    public function setUp()
    {
        $this->projectLocation      = sprintf('%s/%s/root', sys_get_temp_dir(), $this->getName(false));
        mkdir($this->projectLocation, 0777, true);
        $this->config               = new ProjectConfig(array('magento-root-dir' => $this->projectLocation), array());
        $this->factory              = new InstallerFactory;
        $this->installer            = $this->factory->make($this->config, new EventManager);
        $this->root                 = vfsStream::setup('root');
        $this->fileSystem           = new Filesystem;
    }

    public function testExceptionIsThrownIfNoMappingTypeCanBeFound()
    {
        $this->setExpectedException(
            'ErrorException',
            'Unable to find deploy strategy for module: "some/package" no known mapping'
        );

        $package = new Package('some/package', '1.0.0', 'some/package');
        $this->installer->install($package, vfsStream::url('root'));
    }

    public function testInstallWithNoMappingsSuccessfullyReturnsEmptyMapCollection()
    {
        $package = new Package('some/package', '1.0.0', 'some/package');

        $extra = array('map' => array());
        $package->setExtra($extra);

        $mappings = $this->installer->install($package, vfsStream::url('root'));
        $this->assertCount(0, $mappings);
    }

    public function tearDown()
    {
        $this->fileSystem->remove($this->projectLocation);
    }
}
