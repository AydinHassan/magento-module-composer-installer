<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Event\PackagePostInstallEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;

/**
 * Class GitIgnoreListenerTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnoreListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GitIgnoreListener
     */
    protected $listener;

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    public function setUp()
    {
        $this->gitIgnore = $this->getMockBuilder('MagentoHackathon\Composer\Magento\GitIgnore')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new GitIgnoreListener($this->gitIgnore);
    }

    public function testRemoveUnInstalledFile()
    {
        $files      = array('file1', 'file2', 'folder/file3');
        $package    = new InstalledPackage('some/package', '1.0.0', $files);
        $event      = new PackageUnInstallEvent('package-uninstall', $package);

        $this->gitIgnore
            ->expects($this->once())
            ->method('removeMultipleEntries')
            ->with($files);

        $this->gitIgnore
            ->expects($this->once())
            ->method('write');

        $this->listener->removeUnInstalledFiles($event);
    }
}
