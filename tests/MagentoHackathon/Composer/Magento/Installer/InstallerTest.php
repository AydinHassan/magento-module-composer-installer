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

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var string
     */
    protected $projectLocation;

    /**
     * @var string
     */
    protected $moduleLocation;

    /**
     * @var InstallerFactory
     */
    protected $factory;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    public function setUp()
    {
        $this->projectLocation      = sprintf('%s/%s/project', realpath(sys_get_temp_dir()), $this->getName(false));
        $this->moduleLocation       = sprintf('%s/%s/module', realpath(sys_get_temp_dir()), $this->getName(false));
        $this->config               = new ProjectConfig(array('magento-root-dir' => $this->projectLocation), array());
        $this->factory              = new InstallerFactory;
        $this->installer            = $this->factory->make($this->config, new EventManager);
        $this->fileSystem           = new Filesystem;

        mkdir($this->projectLocation, 0777, true);
        mkdir($this->moduleLocation, 0777, true);
        vfsStream::setup('root');
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

    public function testIgnoredFilesAreNotInstalled()
    {
        $package = new Package('some/package', '1.0.0', 'some/package');

        $extra = array('map' => array(array('app/etc/modules/*.xml', 'app/etc/modules')));
        $package->setExtra($extra);

        mkdir(sprintf('%s/app/etc/modules', $this->moduleLocation), 0777, true);
        touch(sprintf('%s/app/etc/modules/SomePackage.xml', $this->moduleLocation));
        touch(sprintf('%s/app/etc/modules/SomeOtherPackage.xml', $this->moduleLocation));

        $installer = $this->getInstaller(
            array(
                'magento-deploy-ignore' => array(
                    'some/package' => array(
                        'app/etc/modules/SomePackage.xml'
                    ),
                ),
            )
        );

        $installer->install($package, $this->moduleLocation);
        $this->assertFileNotExists(sprintf('%s/app/etc/modules/SomePackage.xml', $this->projectLocation));
        $this->assertFileExists(sprintf('%s/app/etc/modules/SomeOtherPackage.xml', $this->projectLocation));
    }

    public function testMissingSourceFilesAreSkipped()
    {
        $package = new Package('some/package', '1.0.0', 'some/package');

        $extra = array('map' => array(
            array('app/etc/modules/local.xml', 'app/etc/modules/'),
            array('app/etc/modules/local2.xml', 'app/etc/modules/'),
        ));
        $package->setExtra($extra);

        mkdir(sprintf('%s/app/etc/modules', $this->moduleLocation), 0777, true);
        touch(sprintf('%s/app/etc/modules/local2.xml', $this->moduleLocation));

        $installer = $this->getInstaller();

        $installer->install($package, $this->moduleLocation);
        $this->assertFileNotExists(sprintf('%s/app/etc/modules/local.xml', $this->projectLocation));
        $this->assertFileExists(sprintf('%s/app/etc/modules/local2.xml', $this->projectLocation));
    }

    public function testExceptionIsThrownIfTargetExists()
    {
        $package = new Package('some/package', '1.0.0', 'some/package');

        $extra = array('map' => array(
            array('app/etc/modules/local.xml', 'app/etc/modules/'),
            array('app/etc/modules/local2.xml', 'app/etc/modules/'),
        ));
        $package->setExtra($extra);

        mkdir(sprintf('%s/app/etc/modules', $this->moduleLocation), 0777, true);
        touch(sprintf('%s/app/etc/modules/local.xml', $this->moduleLocation));
        touch(sprintf('%s/app/etc/modules/local2.xml', $this->moduleLocation));
        mkdir(sprintf('%s/app/etc/modules', $this->projectLocation), 0777, true);
        touch(sprintf('%s/app/etc/modules/local2.xml', $this->projectLocation));

        $installer = $this->getInstaller();

        $file = sprintf('%s/app/etc/modules/local2.xml', $this->projectLocation);
        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException',
            sprintf('Target "%s" already exists (set extra.magento-force to override)', $file)
        );
        $installer->install($package, $this->moduleLocation);
        $this->assertFileExists(sprintf('%s/app/etc/modules/local2.xml', $this->projectLocation));
        $this->assertFileExists(sprintf('%s/app/etc/modules/local2.xml', $this->projectLocation));
    }

    public function testDestinationDirectoryIsCreatedIfMapEndsWithDirectorySeparator()
    {
        $package = new Package('some/package', '1.0.0', 'some/package');

        $extra = array('map' => array(
            array('app/etc/modules/local.xml', 'app/etc/modules/'),
        ));
        $package->setExtra($extra);

        mkdir(sprintf('%s/app/etc/modules', $this->moduleLocation), 0777, true);
        touch(sprintf('%s/app/etc/modules/local.xml', $this->moduleLocation));

        $installer = $this->getInstaller();
        $installer->install($package, $this->moduleLocation);

        $this->assertTrue(is_dir(sprintf('%s/app/etc/modules', $this->projectLocation)));
        $this->assertFileExists(sprintf('%s/app/etc/modules/local.xml', $this->projectLocation));
    }

    /**
     * @param array $config
     * @return Installer
     */
    protected function getInstaller(array $config = array())
    {
        $config['magento-root-dir'] = $this->projectLocation;
        $config       = new ProjectConfig($config, array());
        $factory      = new InstallerFactory;
        $installer    = $factory->make($config, new EventManager);
        return $installer;
    }

    public function tearDown()
    {
        $this->fileSystem->remove($this->projectLocation);
        $this->fileSystem->remove($this->moduleLocation);
    }
}
