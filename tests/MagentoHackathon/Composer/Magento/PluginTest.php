<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use Composer\Script\CommandEvent;
use org\bovigo\vfs\vfsStream;
use ReflectionObject;

/**
 * Class PluginTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Composer
     */
    protected $composer;
    protected $io;
    protected $config;
    protected $root;
    protected $magentoDir;

    /**
     * @var Plugin
     */
    protected $plugin;

    public function setUp()
    {
        $this->composer = new Composer;
        $this->config = $this->getMock('Composer\Config');
        $this->composer->setConfig($this->config);
        $this->root = vfsStream::setup('root', null, array('vendor' => array('bin' => array()), 'htdocs' => array()));

        $this->config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($value) {
                switch ($value) {
                    case 'vendor-dir':
                        return vfsStream::url('root/vendor');
                    case 'bin-dir':
                        return vfsStream::url('root/vendor/bin');
                }
            }));

        $this->config->expects($this->any())
            ->method('all')
            ->will($this->returnValue(array(
                'repositories' => array(),
                'config' => array(
                    'vendor-dir' => vfsStream::url('root/vendor'),
                    'bin-dir' => vfsStream::url('root/vendor/bin'),
                ),
            )));

        $this->composer->setInstallationManager(new InstallationManager());
        $this->io = $this->getMock('Composer\IO\IOInterface');

        $this->io
            ->expects($this->any())
            ->method('isDebug')
            ->will($this->returnValue(true));

        $this->plugin = new Plugin;

        $repoManager = new RepositoryManager($this->io, $this->config);
        $repoManager->setLocalRepository(new WritableArrayRepository);

        $this->composer->setRepositoryManager($repoManager);

    }

    public function testActivate()
    {
        $this->composer->setPackage($this->createRootPackage());
        $this->plugin->activate($this->composer, $this->io);
    }

    public function testGetSubscribedEvents()
    {
        $expected = array(
            'post-install-cmd'  => array(array('onNewCodeEvent', 0)),
            'post-update-cmd'   => array(array('onNewCodeEvent', 0)),
        );

        $this->assertSame($expected, Plugin::getSubscribedEvents());
    }

    public function testOnlyMagentoModulePackagesArePassedToModuleManager()
    {
        $this->composer->setPackage($this->createRootPackage());
        $this->plugin->activate($this->composer, $this->io);

        $moduleManagerMock = $this->getMockBuilder('MagentoHackathon\Composer\Magento\ModuleManager')
            ->disableOriginalConstructor()
            ->setMethods(array('updateInstalledPackages'))
            ->getMock();

        $refObject   = new ReflectionObject($this->plugin);
        $refProperty = $refObject->getProperty('moduleManager');
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->plugin, $moduleManagerMock);

        $mPackage1      = $this->createPackage('magento/module1', 'magento-module');
        $mPackage2      = $this->createPackage('magento/module2', 'magento-module');
        $normalPackage  = $this->createPackage('normal/module', 'other-module');

        $lRepository    = $this->composer->getRepositoryManager()->getLocalRepository();
        $lRepository->addPackage($mPackage1);
        $lRepository->addPackage($mPackage2);
        $lRepository->addPackage($normalPackage);

        $moduleManagerMock
            ->expects($this->once())
            ->method('updateInstalledPackages')
            ->with(array($mPackage1, $mPackage2));

        $this->plugin->onNewCodeEvent(new CommandEvent('event', $this->composer, $this->io));
    }

    /**
     * @param array $extra
     * @return RootPackage
     */
    private function createRootPackage(array $extra = array())
    {
        $package = new RootPackage("root/package", "1.0.0", "root/package");
        $extra['magento-root-dir'] = vfsStream::url('root/htdocs');
        $package->setExtra($extra);
        return $package;
    }

    /**
     * @param string $name
     * @param string $type
     * @return Package
     */
    private function createPackage($name, $type)
    {
        $package = new Package($name, '1.0.0', $name);
        $package->setType($type);
        return $package;
    }
}
