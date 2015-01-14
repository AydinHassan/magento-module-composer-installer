<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Factory\InstallerFactory;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;

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

    public function setUp()
    {
        $this->projectLocation      = sprintf('%s/%s/root', sys_get_temp_dir(), $this->getName(false));
        mkdir($this->projectLocation, 0777, true);
        $this->config               = new ProjectConfig(array('magento-root-dir' => $this->projectLocation), array());
        $this->factory              = new InstallerFactory;
        $this->installer            = $this->factory->make($this->config, new EventManager);
        $this->root                 = vfsStream::setup('root');
        $this->testPackageLocation  = __DIR__ . '/../../../../res/real-packages';
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

    public function testDansloModule()
    {
        $sourcePath = sprintf('%s/danslo-apiimport-1.1.1', $this->testPackageLocation);

        $package = $this->loadPackage($sourcePath, '1.1.1');
        $this->installer->install($package, $sourcePath);
    }

    /**
     * @param string $dir
     * @param string $version
     *
     * @return \Composer\Package\CompletePackage
     */
    public function loadPackage($dir, $version)
    {
        $moduleLocation = sprintf('%s/composer.json', $dir);

        if (!file_exists($moduleLocation)) {
            throw new \RuntimeException(sprintf('Cannot find module "%s"', $moduleLocation));
        }

        $loader         = new ArrayLoader;
        $jsonContent    = json_decode(file_get_contents($moduleLocation), true);
        $jsonContent['version'] = $version;
        $package        = $loader->load($jsonContent);
        return $package;
    }

    /**
     * @dataProvider moduleProvider
     *
     * @param string $name
     * @param string $version
     * @param array $methods
     */
    public function testRealModules($name, $version, array $methods)
    {
        $sourcePath = sprintf('%s/%s', $this->testPackageLocation, $name);
        $package = $this->loadPackage($sourcePath, $version);

        foreach ($methods as $installMethod => $expectedFiles) {
            $extra = array('magento-root-dir' => $this->projectLocation, 'magento-deploystrategy' => $installMethod);
            $config = new ProjectConfig($extra, array());
            $installer = $this->factory->make($config, new EventManager);
            $installer->install($package, $sourcePath);

            $method = null;
            switch($installMethod) {
                case 'symlink':
                    $method = 'is_link';
                    break;
                case 'link':
                    $method = 'is_link';
                    break;
                case 'copy':
                    $method = 'is_file';
                    break;
            }

            foreach ($expectedFiles as $file) {
                $file = sprintf('%s/%s', $this->projectLocation, $file);
                $this->assertFileExists($file);
                $this->assertTrue($method($file));
            }
        }
    }

    /**
     * @return array
     */
    public function moduleProvider()
    {
        return array(
            array(
                'name'      => 'danslo-apiimport-1.1.1',
                'version'   => '1.1.1',
                'destinationFiles' => array(
                    'symlink' => array(
                        'app/etc/modules/Danslo_ApiImport.xml',
                        'app/code/local/Danslo/ApiImport',
                    )
                )
            )
        );
    }

    public function tearDown()
    {
        $fs = new \Composer\Util\Filesystem();
        $fs->remove($this->projectLocation);
    }
}
