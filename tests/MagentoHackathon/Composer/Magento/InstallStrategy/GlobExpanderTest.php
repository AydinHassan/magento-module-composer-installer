<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;

/**
 * Class GlobExpanderTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GlobExpanderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var string
     */
    protected $root;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    public function setUp()
    {
        $this->root = sprintf('%s/globtest', sys_get_temp_dir());
        $this->fileSystem = new Filesystem;
        $this->fileSystem->remove($this->root);
        mkdir(sprintf('%s/globtest', sys_get_temp_dir()));
    }

    public function testGlobsAreExpanded()
    {
        $mappings = array(
            array('*.php', '/'),
            array('/directory/*', '/dir'),
        );

        mkdir(sprintf('%s/source', $this->root));
        mkdir(sprintf('%s/destination', $this->root));
        touch(sprintf('%s/source/1.php', $this->root));
        touch(sprintf('%s/source/2.php', $this->root));
        mkdir(sprintf('%s/source/directory', $this->root));
        touch(sprintf('%s/source/directory/1.txt', $this->root));
        touch(sprintf('%s/source/directory/2.txt', $this->root));

        $globExpander = new GlobExpander(
            sprintf('%s/source', $this->root),
            sprintf('%s/destination', $this->root),
            $mappings
        );

        $expected = array(
            array('1.php', ''),
            array('2.php', ''),
            array('directory/1.txt', 'dir'),
            array('directory/2.txt', 'dir'),
        );

        $this->assertSame($expected, $globExpander->getMappings());
    }

    public function tearDown()
    {
        $this->fileSystem->remove($this->root);
    }
}
