<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Util\Filesystem;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Finder\Expression\Glob;

/**
 * Class GlobResolverTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategyOld
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GlobResolverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var string
     */
    protected $root;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var GlobResolver
     */
    protected $globResolver;

    public function setUp()
    {
        $this->root = sprintf('%s/globtest', sys_get_temp_dir());
        $this->fileSystem = new Filesystem;
        $this->fileSystem->remove($this->root);
        mkdir(sprintf('%s/globtest', sys_get_temp_dir()));
        $this->globResolver = new GlobResolver;
    }

    public function testGlobsAreExpanded()
    {
        $mappings = array(
            new Map('*.php', '/', sprintf('%s/source', $this->root), '/'),
            new Map('/directory/*', '/dir', sprintf('%s/source', $this->root), '/'),
        );

        $mappings = new MapCollection($mappings);

        mkdir(sprintf('%s/source', $this->root));
        mkdir(sprintf('%s/destination', $this->root));
        touch(sprintf('%s/source/1.php', $this->root));
        touch(sprintf('%s/source/2.php', $this->root));
        mkdir(sprintf('%s/source/directory', $this->root));
        touch(sprintf('%s/source/directory/1.txt', $this->root));
        touch(sprintf('%s/source/directory/2.txt', $this->root));

        $expected = array(
            array('1.php', '1.php', sprintf('%s/source/1.php', $this->root), '/1.php'),
            array('2.php', '2.php', sprintf('%s/source/2.php', $this->root), '/2.php'),
            array('directory/1.txt', 'dir/1.txt', sprintf('%s/source/directory/1.txt', $this->root), '/dir/1.txt'),
            array('directory/2.txt', 'dir/2.txt', sprintf('%s/source/directory/2.txt', $this->root), '/dir/2.txt'),
        );

        $resolvedMappings = $this->globResolver->resolve($mappings);
        //$this->assertSame($expected, $resolvedMappings);
        $this->assertContainsOnlyInstancesOf('\MagentoHackathon\Composer\Magento\Map\Map', $resolvedMappings);
        $this->assertCount(4, $resolvedMappings);

        $maps = $resolvedMappings->all();

        $this->assertSame($expected[0][0], $maps[0]->getSource());
        $this->assertSame($expected[1][0], $maps[1]->getSource());
        $this->assertSame($expected[2][0], $maps[2]->getSource());
        $this->assertSame($expected[3][0], $maps[3]->getSource());

        $this->assertSame($expected[0][1], $maps[0]->getDestination());
        $this->assertSame($expected[1][1], $maps[1]->getDestination());
        $this->assertSame($expected[2][1], $maps[2]->getDestination());
        $this->assertSame($expected[3][1], $maps[3]->getDestination());

        $this->assertSame($expected[0][2], $maps[0]->getAbsoluteSource());
        $this->assertSame($expected[1][2], $maps[1]->getAbsoluteSource());
        $this->assertSame($expected[2][2], $maps[2]->getAbsoluteSource());
        $this->assertSame($expected[3][2], $maps[3]->getAbsoluteSource());

        $this->assertSame($expected[0][3], $maps[0]->getAbsoluteDestination());
        $this->assertSame($expected[1][3], $maps[1]->getAbsoluteDestination());
        $this->assertSame($expected[2][3], $maps[2]->getAbsoluteDestination());
        $this->assertSame($expected[3][3], $maps[3]->getAbsoluteDestination());
    }

    public function testIfGlobIsDirectoryDirectoryIsAddedToDestination()
    {
        mkdir(sprintf('%s/source/app/code', $this->root), 0777, true);
        mkdir(sprintf('%s/destination', $this->root));
        touch(sprintf('%s/source/app/code/test.php', $this->root));

        $mappings = array(
            array('*', '')
        );

        $expected = array(
            array('app', 'app'),
        );

        $resolvedMappings = $this->globResolver->resolve(sprintf('%s/source', $this->root), $mappings);
        $this->assertSame($expected, $resolvedMappings);
    }

    public function testFileDestinationIncludesFileName()
    {
        mkdir(sprintf('%s/source/sourcedir', $this->root), 0777, true);
        touch(sprintf('%s/source/sourcedir/test1.xml', $this->root));
        touch(sprintf('%s/source/sourcedir/test2.xml', $this->root));

        $mappings = array(
            array('sourcedir/*', 'targetdir')
        );

        $expected = array (
            array('sourcedir/test1.xml', 'targetdir/test1.xml'),
            array('sourcedir/test2.xml', 'targetdir/test2.xml'),
        );

        $resolvedMappings = $this->globResolver->resolve(sprintf('%s/source', $this->root), $mappings);
        $this->assertSame($expected, $resolvedMappings);
    }

    public function testSourceNotFoundExceptionIsThrownIfNoGlobResults()
    {
        mkdir(sprintf('%s/source/sourcedir', $this->root), 0777, true);

        $mappings = array(
            array('sourcedir/*', 'targetdir')
        );

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\SourceNotExistsException'
        );

        $this->globResolver->resolve(sprintf('%s/source', $this->root), $mappings);
    }


    public function testIfMappingIsAFileMappingIsReturnedAsIs()
    {
        mkdir(sprintf('%s/source/sourcedir', $this->root), 0777, true);
        touch(sprintf('%s/source/sourcedir/test1.xml', $this->root));
        touch(sprintf('%s/source/sourcedir/test2.xml', $this->root));

        $mappings = array(
            array('sourcedir/test1.xml', 'targetdir')
        );
        
        $expected = array (
            array('sourcedir/test1.xml', 'targetdir'),
        );

        $resolvedMappings = $this->globResolver->resolve(sprintf('%s/source', $this->root), $mappings);
        $this->assertSame($expected, $resolvedMappings);
    }

    public function tearDown()
    {
        $this->fileSystem->remove($this->root);
    }
}
