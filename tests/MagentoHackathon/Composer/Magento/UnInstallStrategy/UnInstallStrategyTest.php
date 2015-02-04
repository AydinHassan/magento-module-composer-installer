<?php

namespace MagentoHackathon\Composer\Magento\UnInstallStrategy;

use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use MagentoHackathon\Composer\Magento\Util\FileSystem;
use PHPUnit_Framework_TestCase;

/**
 * Class UnInstallStrategyTest
 * @package MagentoHackathon\Composer\Magento\UnInstallStrategy
 */
class UnInstallStrategyTest extends PHPUnit_Framework_TestCase
{
    protected $testDirectory;

    public function testUnInstall()
    {
        $this->testDirectory    = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        $rootDir                = $this->testDirectory . '/root';
        mkdir($rootDir, 0777, true);

        $strategy   = new UnInstallStrategy(new FileSystem, $rootDir);

        mkdir($rootDir . '/child1/secondlevelchild1', 0777, true);
        mkdir($rootDir . '/child2/secondlevelchild2', 0777, true);
        mkdir($rootDir . '/child3/secondlevelchild3', 0777, true);
        mkdir($rootDir . '/child4/secondlevelchild4', 0777, true);

        touch($rootDir . '/child1/secondlevelchild1/file1.txt');
        touch($rootDir . '/child2/secondlevelchild2/file2.txt');
        touch($rootDir . '/child3/secondlevelchild3/file3.txt');
        touch($rootDir . '/child4/secondlevelchild4/file4.txt');

        $maps = new MapCollection(array(
           new Map('file1', '/child1/secondlevelchild1/file1.txt', $rootDir, $rootDir),
           new Map('file1', '/child2/secondlevelchild2/file2.txt', $rootDir, $rootDir),
           new Map('file1', '/child3/secondlevelchild3/file3.txt', $rootDir, $rootDir),
           new Map('file1', '/child4/secondlevelchild4/file4.txt', $rootDir, $rootDir),
        ));

        $strategy->unInstall($maps);

        $this->assertFileExists($rootDir);
        $this->assertFileNotExists($rootDir . '/child1');
        $this->assertFileNotExists($rootDir . '/child2');
        $this->assertFileNotExists($rootDir . '/child3');
        $this->assertFileNotExists($rootDir . '/child4');
    }

    public function testUnInstallDoesNotRemoveOtherFiles()
    {
        $this->testDirectory    = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        $rootDir                = $this->testDirectory . '/root';
        mkdir($rootDir, 0777, true);

        $strategy   = new UnInstallStrategy(new FileSystem, $rootDir);

        mkdir($rootDir . '/child1/secondlevelchild1', 0777, true);
        mkdir($rootDir . '/child2/secondlevelchild2', 0777, true);
        mkdir($rootDir . '/child3/secondlevelchild3', 0777, true);
        mkdir($rootDir . '/child4/secondlevelchild4', 0777, true);

        touch($rootDir . '/child1/secondlevelchild1/file1.txt');
        touch($rootDir . '/child2/secondlevelchild2/file2.txt');
        touch($rootDir . '/child3/secondlevelchild3/file3.txt');
        touch($rootDir . '/child4/secondlevelchild4/file4.txt');
        touch($rootDir . '/child4/secondlevelchild4/file5.txt');

        $maps = new MapCollection(array(
            new Map('file1', '/child1/secondlevelchild1/file1.txt', $rootDir, $rootDir),
            new Map('file1', '/child2/secondlevelchild2/file2.txt', $rootDir, $rootDir),
            new Map('file1', '/child3/secondlevelchild3/file3.txt', $rootDir, $rootDir),
            new Map('file1', '/child4/secondlevelchild4/file4.txt', $rootDir, $rootDir),
        ));

        $strategy->unInstall($maps);

        $this->assertFileExists($rootDir);
        $this->assertFileNotExists($rootDir . '/child1');
        $this->assertFileNotExists($rootDir . '/child2');
        $this->assertFileNotExists($rootDir . '/child3');
        $this->assertFileNotExists($rootDir . '/child4/secondlevelchild4/file4.txt');
        $this->assertFileExists($rootDir . '/child4/secondlevelchild4/file5.txt');
    }

    public function tearDown()
    {
        $fs = new FileSystem;
        $fs->remove($this->testDirectory);
    }
}
