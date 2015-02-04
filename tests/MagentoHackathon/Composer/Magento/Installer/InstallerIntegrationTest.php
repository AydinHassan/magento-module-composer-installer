<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Factory\InstallerFactory;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Util\FileSystem;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

/**
 * Class InstallerIntegrationTest
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class InstallerIntegrationTest extends PHPUnit_Framework_TestCase
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
        $this->projectLocation      = realpath($this->projectLocation);
        $this->config               = new ProjectConfig(['magento-root-dir' => $this->projectLocation], []);
        $this->factory              = new InstallerFactory;
        $this->installer            = $this->factory->make($this->config, new EventManager);
        $this->root                 = vfsStream::setup('root');
        $this->testPackageLocation  = realpath(__DIR__ . '/../../../../res/real-packages');
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
     * @dataProvider moduleProviderSymlink
     *
     * @param string $name
     * @param string $version
     * @param array $destinationFiles
     */
    public function testRealModulesSymlink($name, $version, array $destinationFiles)
    {
        $sourcePath = sprintf('%s/%s', $this->testPackageLocation, $name);
        $this->unzipPackage($sourcePath);

        $package    = $this->loadPackage($sourcePath, $version);
        $extra      = ['magento-root-dir' => $this->projectLocation, 'magento-deploystrategy' => 'symlink'];
        $config     = new ProjectConfig($extra, []);
        $installer  = $this->factory->make($config, new EventManager);
        $installer->install($package, $sourcePath);

        foreach ($destinationFiles as $file) {
            $file = sprintf('%s/%s', $this->projectLocation, $file);
            $this->assertFileExists($file);
            $this->assertTrue(is_link($file));
        }

        $fs = new FileSystem();
        $fs->remove($sourcePath);
    }

    /**
     * @param string $packageLocation
     */
    public function unzipPackage($packageLocation)
    {
        $zipArchive = new \ZipArchive();
        $result = $zipArchive->open($packageLocation . '.zip');
        if (true === $result) {
            $zipArchive->extractTo($this->testPackageLocation);
            $zipArchive->close();
        }
    }

    /**
     * @return array
     */
    public function moduleProviderSymlink()
    {
        return [
            [
                'name'      => 'danslo-apiimport-1.1.1',
                'version'   => '1.1.1',
                'destinationFiles' => [
                    'app/etc/modules/Danslo_ApiImport.xml',
                    'app/code/local/Danslo/ApiImport',
                ],
            ],
            [
                'name'      => 'ecomdev-phpunit-0.3.7',
                'version'   => '0.3.7',
                'destinationFiles' => [
                    'shell/ecomdev-phpunit.php',
                    'lib/vfsStream',
                    'lib/Spyc',
                    'lib/EcomDev/PHPUnit',
                    'lib/EcomDev/Utils',
                    'app/code/community/EcomDev/PHPUnit',
                    'app/code/community/EcomDev/PHPUnitTest',
                    'app/code/community/EcomDev/PHPUnitTest',
                    'app/etc/modules/EcomDev_PHPUnit.xml',
                    'app/etc/modules/EcomDev_PHPUnitTest.xml',
                ],
            ],
            [
                'name'      => 'aoe-scheduler-0.4.3',
                'version'   => '0.4.3',
                'destinationFiles' => [
                    'app/code/community/Aoe/Scheduler',
                    'app/etc/modules/Aoe_Scheduler.xml',
                    'shell/scheduler.php',
                    'app/locale/de_DE/Aoe_Scheduler.csv',
                    'app/locale/en_US/template/email/aoe_scheduler',
                    'app/design/adminhtml/default/default/template/aoe_scheduler',
                    'app/design/adminhtml/default/default/layout/aoe_scheduler',
                    'skin/adminhtml/default/default/aoe_scheduler',
                    'var/connect/Aoe_Scheduler.xml',
                ],
            ],
            [
                'name'      => 'magento-turpentine-0.6.1',
                'version'   => '0.4.3',
                'destinationFiles' => [
                    'app/etc/modules/Nexcessnet_Turpentine.xml',
                    'app/code/community/Nexcessnet/Turpentine',
                    'app/code/local/Mage/Core/Model/Session.php',
                    'app/design/adminhtml/default/default/layout/turpentine.xml',
                    'app/design/adminhtml/default/default/template/turpentine',
                    'app/design/frontend/base/default/layout/turpentine_esi.xml',
                    'app/design/frontend/base/default/template/turpentine',
                    'shell/varnishadm.php',
                ],
            ],
        ];
    }

    /**
     * @dataProvider moduleProviderCopy
     *
     * @param string $name
     * @param string $version
     * @param array $destinationFiles
     */
    public function testRealModulesCopy($name, $version, array $destinationFiles)
    {
        $sourcePath = sprintf('%s/%s', $this->testPackageLocation, $name);
        $this->unzipPackage($sourcePath);

        $package    = $this->loadPackage($sourcePath, $version);
        $extra      = ['magento-root-dir' => $this->projectLocation, 'magento-deploystrategy' => 'copy'];
        $config     = new ProjectConfig($extra, []);
        $installer  = $this->factory->make($config, new EventManager);
        $installer->install($package, $sourcePath);

        foreach ($destinationFiles as $file) {
            $file = sprintf('%s/%s', $this->projectLocation, $file);
            $this->assertFileExists($file);
            $this->assertTrue(is_file($file));
            $this->assertFalse(is_link($file));
        }

        $fs = new FileSystem();
        $fs->remove($sourcePath);
    }

    /**
     * @return array
     */
    public function moduleProviderCopy()
    {
        // @codingStandardsIgnoreStart
        return [
            [
                'name'      => 'danslo-apiimport-1.1.1',
                'version'   => '1.1.1',
                'destinationFiles' => [
                    'app/etc/modules/Danslo_ApiImport.xml',
                    'app/code/local/Danslo/ApiImport/etc/api.xml',
                    'app/code/local/Danslo/ApiImport/etc/config.xml',
                    'app/code/local/Danslo/ApiImport/etc/system.xml',
                    'app/code/local/Danslo/ApiImport/etc/wsdl.xml',
                    'app/code/local/Danslo/ApiImport/Helper/Data.php',
                    'app/code/local/Danslo/ApiImport/Helper/Test.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Api/V2.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Customer/Address.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Product/Type/Bundle.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Product/Type/Grouped.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Category.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Customer.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Product.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Api.php',
                    'app/code/local/Danslo/ApiImport/Model/Resource/Import/Data.php',
                    'app/code/local/Danslo/ApiImport/Model/Resource/Import/Data.php',
                    'app/code/local/Danslo/ApiImport/Model/Import.php',
                    'app/code/local/Danslo/ApiImport/Model/Observer.php',
                ],
            ],
            [
                'name'      => 'ecomdev-phpunit-0.3.7',
                'version'   => '0.3.7',
                'destinationFiles' => [
                    'app/etc/modules/EcomDev_PHPUnitTest.xml',
                    'app/etc/modules/EcomDev_PHPUnit.xml',
                    'app/code/community/EcomDev/PHPUnit/bootstrap.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Layout.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/ProcessorInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Attributes.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Config.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Tables.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Eav.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Vfs.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Registry.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Cache.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Scope.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Vfs.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Design/Package.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Config.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Expectation/Object.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Expectation/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Observer.php',
                    'app/code/community/EcomDev/PHPUnit/Model/LoadableInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/App/Area.php',
                    'app/code/community/EcomDev/PHPUnit/Model/App.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Test/Loadable/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Complex/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Exception.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/AbstractComplex.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Attribute/Default.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Attribute/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/AbstractAttribute.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Default.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Catalog/Product.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Catalog/Category.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Catalog/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/AbstractEav.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/RestoreAwareInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture.php',
                    'app/code/community/EcomDev/PHPUnit/Model/FixtureInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/AbstractLoader.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Module.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Default.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Global.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/LoaderInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Expectation.php',
                    'app/code/community/EcomDev/PHPUnit/Model/ExpectationInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Suite.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Listener.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Config.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Guest.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Observer.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Mock.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Customer.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Session.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Controller.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Util.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Suite/Group.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case.php',
                    'app/code/community/EcomDev/PHPUnit/etc/config.xml',
                    'app/code/community/EcomDev/PHPUnit/Controller/Front.php',
                    'app/code/community/EcomDev/PHPUnit/Controller/Response/Http.php',
                    'app/code/community/EcomDev/PHPUnit/Controller/Request/Http.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Call.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Observer.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Mock.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/_data/dp-testCustomerSession.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/_data/fx-customers.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/_data/ex-testCustomerSession.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Customer.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Session.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Model/Fixture/fixtures/testFixtureArrayMerge.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Model/Fixture.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/AbstractConstraint.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Mock/Proxy.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Helper.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node/providers/testEqualsXml.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node/providers/testEqualsXmlFailure.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node/providers/testConstructorAccepts.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/fixtures/files.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/providers/testGetVersionScriptsDiff.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/providers/testParseVersions.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/expectations/testGetVersionScriptsDiff.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/expectations/testParseVersions.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/Script.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/AbstractHelper.php',
                    'app/code/community/EcomDev/PHPUnitTest/etc/config.xml',
                    'shell/ecomdev-phpunit.php',
                    'lib/Spyc/spyc.php',
                    'lib/EcomDev/PHPUnit/Design/Package/Interface.php',
                    'lib/EcomDev/PHPUnit/Design/PackageInterface.php',
                    'lib/EcomDev/PHPUnit/Helper/Listener/Interface.php',
                    'lib/EcomDev/PHPUnit/Helper/Interface.php',
                    'lib/EcomDev/PHPUnit/Helper/Abstract.php',
                    'lib/EcomDev/PHPUnit/Helper/ListenerInterface.php',
                    'lib/EcomDev/PHPUnit/AbstractConstraint.php',
                    'lib/EcomDev/PHPUnit/Mock/Proxy.php',
                    'lib/EcomDev/PHPUnit/Helper.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout.php',
                    'lib/EcomDev/PHPUnit/Constraint/ConfigInterface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config.php',
                    'lib/EcomDev/PHPUnit/Constraint/AbstractLayout.php',
                    'lib/EcomDev/PHPUnit/Constraint/Exception.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Layout.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Module.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/TableAlias.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/EventObserver.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Route.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Interface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/ClassAlias.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Node.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Resource/Script.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Resource.php',
                    'lib/EcomDev/PHPUnit/Constraint/Json.php',
                    'lib/EcomDev/PHPUnit/Constraint/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/LoggerInterface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Handle.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Logger/Interface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Block/Action.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Block/Property.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Block.php',
                    'lib/EcomDev/PHPUnit/Constraint/Or.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Request.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/AbstractResponse.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Response/Header.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Response/Body.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Response/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/AbstractConfig.php',
                    'lib/EcomDev/PHPUnit/Isolation/Interface.php',
                    'lib/EcomDev/PHPUnit/Controller/RequestInterface.php',
                    'lib/EcomDev/PHPUnit/Controller/ResponseInterface.php',
                    'lib/EcomDev/PHPUnit/Controller/Response/Interface.php',
                    'lib/EcomDev/PHPUnit/Controller/Request/Interface.php',
                    'lib/EcomDev/PHPUnit/HelperInterface.php',
                    'lib/EcomDev/PHPUnit/IsolationInterface.php',
                    'lib/EcomDev/PHPUnit/AbstractHelper.php',
                    'lib/EcomDev/Utils/Reflection.php',
                ],
            ],
            [
                'name'      => 'aoe-scheduler-0.4.3',
                'version'   => '0.4.3',
                'destinationFiles' => [
                    'skin/adminhtml/default/default/aoe_scheduler/StyleSheet/timeline.css',
                    'skin/adminhtml/default/default/aoe_scheduler/StyleSheet/bars.css',
                    'skin/adminhtml/default/default/aoe_scheduler/Images/hour.gif',
                    'skin/adminhtml/default/default/aoe_scheduler/Images/gradient.png',
                    'skin/adminhtml/default/default/aoe_scheduler/Images/bg_notifications.gif',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/tooltip.dynamic.js',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/common.js',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/tooltip.js',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/jquery-1.6.2.min.js',
                    'app/locale/en_US/template/email/aoe_scheduler/cron_error.html',
                    'app/locale/de_DE/Aoe_Scheduler.csv',
                    'app/design/adminhtml/default/default/template/aoe_scheduler/timeline.phtml',
                    'app/design/adminhtml/default/default/template/aoe_scheduler/timeline_detail.phtml',
                    'app/design/adminhtml/default/default/layout/aoe_scheduler/aoe_scheduler.xml',
                    'app/etc/modules/Aoe_Scheduler.xml',
                    'app/code/community/Aoe/Scheduler/Helper/Data.php',
                    'app/code/community/Aoe/Scheduler/Model/Api.php',
                    'app/code/community/Aoe/Scheduler/Model/HeartbeatTask.php',
                    'app/code/community/Aoe/Scheduler/Model/TestTask.php',
                    'app/code/community/Aoe/Scheduler/Model/Collection/Crons.php',
                    'app/code/community/Aoe/Scheduler/Model/Observer.php',
                    'app/code/community/Aoe/Scheduler/Model/Schedule.php',
                    'app/code/community/Aoe/Scheduler/Model/Configuration.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/SchedulerController.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/TimelineController.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/CronController.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/AbstractController.php',
                    'app/code/community/Aoe/Scheduler/etc/system.xml',
                    'app/code/community/Aoe/Scheduler/etc/config.xml',
                    'app/code/community/Aoe/Scheduler/etc/api.xml',
                    'app/code/community/Aoe/Scheduler/etc/adminhtml.xml',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/TimelineDetail.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Scheduler/Grid.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Cron/Grid.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Cron.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Timeline.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Scheduler.php',
                ],
            ],
            [
                'name'      => 'magento-turpentine-0.6.1',
                'version'   => '0.4.3',
                'destinationFiles' => [
                    'app/design/frontend/base/default/template/turpentine/ajax.phtml',
                    'app/design/frontend/base/default/template/turpentine/notices.phtml',
                    'app/design/frontend/base/default/template/turpentine/esi.phtml',
                    'app/design/frontend/base/default/layout/turpentine_esi.xml',
                    'app/design/adminhtml/default/default/template/turpentine/varnish_management.phtml',
                    'app/design/adminhtml/default/default/layout/turpentine.xml',
                    'app/etc/modules/Nexcessnet_Turpentine.xml',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Ban.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Debug.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Cron.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Varnish.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Esi.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Data.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Shim/Mage/Core/Layout.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Shim/Mage/Core/Config.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Shim/Mage/Core/App.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Config/Select/Toggle.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Config/Select/Version.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Config/Select/StripWhitespace.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/PageCache/Container/Notices.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Ban.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Debug.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Cron.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Varnish.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Esi.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Configurator/Version2.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Configurator/Version3.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Configurator/Abstract.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Admin.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Admin/Socket.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Dummy/Request.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Session.php',
                    'app/code/community/Nexcessnet/Turpentine/controllers/EsiController.php',
                    'app/code/community/Nexcessnet/Turpentine/controllers/Varnish/ManagementController.php',
                    'app/code/community/Nexcessnet/Turpentine/misc/uuid.c',
                    'app/code/community/Nexcessnet/Turpentine/misc/version-2.vcl',
                    'app/code/community/Nexcessnet/Turpentine/misc/version-3.vcl',
                    'app/code/community/Nexcessnet/Turpentine/etc/system.xml',
                    'app/code/community/Nexcessnet/Turpentine/etc/config.xml',
                    'app/code/community/Nexcessnet/Turpentine/etc/cache.xml',
                    'app/code/community/Nexcessnet/Turpentine/Block/Notices.php',
                    'app/code/community/Nexcessnet/Turpentine/Block/Management.php',
                    'app/code/community/Nexcessnet/Turpentine/Block/Core/Messages.php',
                    'app/code/community/Nexcessnet/Turpentine/Block/Catalog/Product/List/Toolbar.php',
                    'app/code/local/Mage/Core/Model/Session.php',
                ],
            ]
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider moduleProviderLink
     *
     * @param string $name
     * @param string $version
     * @param array $destinationFiles
     */
    public function testRealModulesLink($name, $version, array $destinationFiles)
    {
        $sourcePath = sprintf('%s/%s', $this->testPackageLocation, $name);
        $this->unzipPackage($sourcePath);

        $package    = $this->loadPackage($sourcePath, $version);
        $extra      = ['magento-root-dir' => $this->projectLocation, 'magento-deploystrategy' => 'copy'];
        $config     = new ProjectConfig($extra, []);
        $installer  = $this->factory->make($config, new EventManager);
        $installer->install($package, $sourcePath);

        foreach ($destinationFiles as $file) {
            $file = sprintf('%s/%s', $this->projectLocation, $file);
            $this->assertFileExists($file);
            $this->assertTrue(is_file($file));
            $this->assertFalse(is_link($file));
        }

        $fs = new FileSystem();
        $fs->remove($sourcePath);
    }

    /**
     * @return array
     */
    public function moduleProviderLink()
    {
        // @codingStandardsIgnoreStart
        return [
            [
                'name'      => 'danslo-apiimport-1.1.1',
                'version'   => '1.1.1',
                'destinationFiles' => [
                    'app/etc/modules/Danslo_ApiImport.xml',
                    'app/code/local/Danslo/ApiImport/etc/api.xml',
                    'app/code/local/Danslo/ApiImport/etc/config.xml',
                    'app/code/local/Danslo/ApiImport/etc/system.xml',
                    'app/code/local/Danslo/ApiImport/etc/wsdl.xml',
                    'app/code/local/Danslo/ApiImport/Helper/Data.php',
                    'app/code/local/Danslo/ApiImport/Helper/Test.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Api/V2.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Customer/Address.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Product/Type/Bundle.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Product/Type/Grouped.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Category.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Customer.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Entity/Product.php',
                    'app/code/local/Danslo/ApiImport/Model/Import/Api.php',
                    'app/code/local/Danslo/ApiImport/Model/Resource/Import/Data.php',
                    'app/code/local/Danslo/ApiImport/Model/Resource/Import/Data.php',
                    'app/code/local/Danslo/ApiImport/Model/Import.php',
                    'app/code/local/Danslo/ApiImport/Model/Observer.php',
                ],
            ],
            [
                'name'      => 'ecomdev-phpunit-0.3.7',
                'version'   => '0.3.7',
                'destinationFiles' => [
                    'app/etc/modules/EcomDev_PHPUnitTest.xml',
                    'app/etc/modules/EcomDev_PHPUnit.xml',
                    'app/code/community/EcomDev/PHPUnit/bootstrap.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Layout.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/ProcessorInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Attributes.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Config.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Tables.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Eav.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Vfs.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Registry.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Cache.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Processor/Scope.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture/Vfs.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Design/Package.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Config.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Fixture.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Expectation/Object.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Expectation/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Observer.php',
                    'app/code/community/EcomDev/PHPUnit/Model/LoadableInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/App/Area.php',
                    'app/code/community/EcomDev/PHPUnit/Model/App.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Test/Loadable/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Complex/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Exception.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/AbstractComplex.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Attribute/Default.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Attribute/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/AbstractAttribute.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Default.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Catalog/Product.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Catalog/Category.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/Eav/Catalog/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/AbstractEav.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture/RestoreAwareInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Mysql4/Fixture.php',
                    'app/code/community/EcomDev/PHPUnit/Model/FixtureInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/AbstractLoader.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Module.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Default.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Global.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Interface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/Loader/Abstract.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Yaml/LoaderInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Model/Expectation.php',
                    'app/code/community/EcomDev/PHPUnit/Model/ExpectationInterface.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Suite.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Listener.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Config.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Guest.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Observer.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Mock.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Customer.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Helper/Session.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Controller.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case/Util.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Suite/Group.php',
                    'app/code/community/EcomDev/PHPUnit/Test/Case.php',
                    'app/code/community/EcomDev/PHPUnit/etc/config.xml',
                    'app/code/community/EcomDev/PHPUnit/Controller/Front.php',
                    'app/code/community/EcomDev/PHPUnit/Controller/Response/Http.php',
                    'app/code/community/EcomDev/PHPUnit/Controller/Request/Http.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Call.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Observer.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Mock.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/_data/dp-testCustomerSession.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/_data/fx-customers.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/_data/ex-testCustomerSession.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Customer.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Helper/Session.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Model/Fixture/fixtures/testFixtureArrayMerge.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Model/Fixture.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/AbstractConstraint.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Mock/Proxy.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Helper.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node/providers/testEqualsXml.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node/providers/testEqualsXmlFailure.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node/providers/testConstructorAccepts.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Node.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/fixtures/files.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/providers/testGetVersionScriptsDiff.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/providers/testParseVersions.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/expectations/testGetVersionScriptsDiff.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/expectations/testParseVersions.yaml',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/Constraint/Config/Resource/Script.php',
                    'app/code/community/EcomDev/PHPUnitTest/Test/Lib/AbstractHelper.php',
                    'app/code/community/EcomDev/PHPUnitTest/etc/config.xml',
                    'shell/ecomdev-phpunit.php',
                    'lib/Spyc/spyc.php',
                    'lib/EcomDev/PHPUnit/Design/Package/Interface.php',
                    'lib/EcomDev/PHPUnit/Design/PackageInterface.php',
                    'lib/EcomDev/PHPUnit/Helper/Listener/Interface.php',
                    'lib/EcomDev/PHPUnit/Helper/Interface.php',
                    'lib/EcomDev/PHPUnit/Helper/Abstract.php',
                    'lib/EcomDev/PHPUnit/Helper/ListenerInterface.php',
                    'lib/EcomDev/PHPUnit/AbstractConstraint.php',
                    'lib/EcomDev/PHPUnit/Mock/Proxy.php',
                    'lib/EcomDev/PHPUnit/Helper.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout.php',
                    'lib/EcomDev/PHPUnit/Constraint/ConfigInterface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config.php',
                    'lib/EcomDev/PHPUnit/Constraint/AbstractLayout.php',
                    'lib/EcomDev/PHPUnit/Constraint/Exception.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Layout.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Module.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/TableAlias.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/EventObserver.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Route.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Interface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/ClassAlias.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Node.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Resource/Script.php',
                    'lib/EcomDev/PHPUnit/Constraint/Config/Resource.php',
                    'lib/EcomDev/PHPUnit/Constraint/Json.php',
                    'lib/EcomDev/PHPUnit/Constraint/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/LoggerInterface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Handle.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Logger/Interface.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Block/Action.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Block/Property.php',
                    'lib/EcomDev/PHPUnit/Constraint/Layout/Block.php',
                    'lib/EcomDev/PHPUnit/Constraint/Or.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Request.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/AbstractResponse.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Response/Header.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Response/Body.php',
                    'lib/EcomDev/PHPUnit/Constraint/Controller/Response/Abstract.php',
                    'lib/EcomDev/PHPUnit/Constraint/AbstractConfig.php',
                    'lib/EcomDev/PHPUnit/Isolation/Interface.php',
                    'lib/EcomDev/PHPUnit/Controller/RequestInterface.php',
                    'lib/EcomDev/PHPUnit/Controller/ResponseInterface.php',
                    'lib/EcomDev/PHPUnit/Controller/Response/Interface.php',
                    'lib/EcomDev/PHPUnit/Controller/Request/Interface.php',
                    'lib/EcomDev/PHPUnit/HelperInterface.php',
                    'lib/EcomDev/PHPUnit/IsolationInterface.php',
                    'lib/EcomDev/PHPUnit/AbstractHelper.php',
                    'lib/EcomDev/Utils/Reflection.php',
                ],
            ],
            [
                'name'      => 'aoe-scheduler-0.4.3',
                'version'   => '0.4.3',
                'destinationFiles' => [
                    'skin/adminhtml/default/default/aoe_scheduler/StyleSheet/timeline.css',
                    'skin/adminhtml/default/default/aoe_scheduler/StyleSheet/bars.css',
                    'skin/adminhtml/default/default/aoe_scheduler/Images/hour.gif',
                    'skin/adminhtml/default/default/aoe_scheduler/Images/gradient.png',
                    'skin/adminhtml/default/default/aoe_scheduler/Images/bg_notifications.gif',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/tooltip.dynamic.js',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/common.js',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/tooltip.js',
                    'skin/adminhtml/default/default/aoe_scheduler/JavaScript/jquery-1.6.2.min.js',
                    'app/locale/en_US/template/email/aoe_scheduler/cron_error.html',
                    'app/locale/de_DE/Aoe_Scheduler.csv',
                    'app/design/adminhtml/default/default/template/aoe_scheduler/timeline.phtml',
                    'app/design/adminhtml/default/default/template/aoe_scheduler/timeline_detail.phtml',
                    'app/design/adminhtml/default/default/layout/aoe_scheduler/aoe_scheduler.xml',
                    'app/etc/modules/Aoe_Scheduler.xml',
                    'app/code/community/Aoe/Scheduler/Helper/Data.php',
                    'app/code/community/Aoe/Scheduler/Model/Api.php',
                    'app/code/community/Aoe/Scheduler/Model/HeartbeatTask.php',
                    'app/code/community/Aoe/Scheduler/Model/TestTask.php',
                    'app/code/community/Aoe/Scheduler/Model/Collection/Crons.php',
                    'app/code/community/Aoe/Scheduler/Model/Observer.php',
                    'app/code/community/Aoe/Scheduler/Model/Schedule.php',
                    'app/code/community/Aoe/Scheduler/Model/Configuration.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/SchedulerController.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/TimelineController.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/CronController.php',
                    'app/code/community/Aoe/Scheduler/controllers/Adminhtml/AbstractController.php',
                    'app/code/community/Aoe/Scheduler/etc/system.xml',
                    'app/code/community/Aoe/Scheduler/etc/config.xml',
                    'app/code/community/Aoe/Scheduler/etc/api.xml',
                    'app/code/community/Aoe/Scheduler/etc/adminhtml.xml',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/TimelineDetail.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Scheduler/Grid.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Cron/Grid.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Cron.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Timeline.php',
                    'app/code/community/Aoe/Scheduler/Block/Adminhtml/Scheduler.php',
                ],
            ],
            [
                'name'      => 'magento-turpentine-0.6.1',
                'version'   => '0.4.3',
                'destinationFiles' => [
                    'app/design/frontend/base/default/template/turpentine/ajax.phtml',
                    'app/design/frontend/base/default/template/turpentine/notices.phtml',
                    'app/design/frontend/base/default/template/turpentine/esi.phtml',
                    'app/design/frontend/base/default/layout/turpentine_esi.xml',
                    'app/design/adminhtml/default/default/template/turpentine/varnish_management.phtml',
                    'app/design/adminhtml/default/default/layout/turpentine.xml',
                    'app/etc/modules/Nexcessnet_Turpentine.xml',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Ban.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Debug.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Cron.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Varnish.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Esi.php',
                    'app/code/community/Nexcessnet/Turpentine/Helper/Data.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Shim/Mage/Core/Layout.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Shim/Mage/Core/Config.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Shim/Mage/Core/App.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Config/Select/Toggle.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Config/Select/Version.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Config/Select/StripWhitespace.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/PageCache/Container/Notices.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Ban.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Debug.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Cron.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Varnish.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Observer/Esi.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Configurator/Version2.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Configurator/Version3.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Configurator/Abstract.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Admin.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Varnish/Admin/Socket.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Dummy/Request.php',
                    'app/code/community/Nexcessnet/Turpentine/Model/Session.php',
                    'app/code/community/Nexcessnet/Turpentine/controllers/EsiController.php',
                    'app/code/community/Nexcessnet/Turpentine/controllers/Varnish/ManagementController.php',
                    'app/code/community/Nexcessnet/Turpentine/misc/uuid.c',
                    'app/code/community/Nexcessnet/Turpentine/misc/version-2.vcl',
                    'app/code/community/Nexcessnet/Turpentine/misc/version-3.vcl',
                    'app/code/community/Nexcessnet/Turpentine/etc/system.xml',
                    'app/code/community/Nexcessnet/Turpentine/etc/config.xml',
                    'app/code/community/Nexcessnet/Turpentine/etc/cache.xml',
                    'app/code/community/Nexcessnet/Turpentine/Block/Notices.php',
                    'app/code/community/Nexcessnet/Turpentine/Block/Management.php',
                    'app/code/community/Nexcessnet/Turpentine/Block/Core/Messages.php',
                    'app/code/community/Nexcessnet/Turpentine/Block/Catalog/Product/List/Toolbar.php',
                    'app/code/local/Mage/Core/Model/Session.php',
                ],
            ]
        ];
        // @codingStandardsIgnoreEnd
    }

    public function tearDown()
    {
        $fs = new \Composer\Util\Filesystem();
        $fs->remove($this->projectLocation);
    }
}
