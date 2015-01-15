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
        mkdir(sprintf('%s/source', $this->root), 0777, true);
        mkdir(sprintf('%s/destination', $this->root));
        $this->globResolver = new GlobResolver;
    }

    public function testGlobsAreExpanded()
    {
        $mappings = array(
            new Map('*.php', '/', sprintf('%s/source', $this->root), sprintf('%s/destination', $this->root)),
            new Map('/directory/*', '/dir', sprintf('%s/source', $this->root), sprintf('%s/destination', $this->root)),
        );

        $mappings = new MapCollection($mappings);

        touch(sprintf('%s/source/1.php', $this->root));
        touch(sprintf('%s/source/2.php', $this->root));
        mkdir(sprintf('%s/source/directory', $this->root));
        touch(sprintf('%s/source/directory/1.txt', $this->root));
        touch(sprintf('%s/source/directory/2.txt', $this->root));

        $resolvedMappings = $this->globResolver->resolve($mappings);
        $this->assertContainsOnlyInstancesOf('\MagentoHackathon\Composer\Magento\Map\Map', $resolvedMappings);
        $this->assertCount(4, $resolvedMappings);

        $expected = array(
            array(
                '1.php',
                '1.php',
                sprintf('%s/source/1.php', $this->root),
                sprintf('%s/destination/1.php', $this->root)
            ),
            array(
                '2.php',
                '2.php',
                sprintf('%s/source/2.php', $this->root),
                sprintf('%s/destination/2.php', $this->root)
            ),
            array(
                'directory/1.txt',
                'dir/1.txt',
                sprintf('%s/source/directory/1.txt', $this->root),
                sprintf('%s/destination/dir/1.txt', $this->root)
            ),
            array(
                'directory/2.txt',
                'dir/2.txt',
                sprintf('%s/source/directory/2.txt', $this->root),
                sprintf('%s/destination/dir/2.txt', $this->root)
            ),
        );

        $maps = $resolvedMappings->all();

        $this->assertSame($expected[0], $this->mapToArray($maps[0]));
        $this->assertSame($expected[1], $this->mapToArray($maps[1]));
        $this->assertSame($expected[2], $this->mapToArray($maps[2]));
        $this->assertSame($expected[3], $this->mapToArray($maps[3]));
    }

    public function testFileDestinationIncludesFileName()
    {
        mkdir(sprintf('%s/source/sourcedir', $this->root));
        touch(sprintf('%s/source/sourcedir/test1.xml', $this->root));
        touch(sprintf('%s/source/sourcedir/test2.xml', $this->root));

        $mappings = array(
            new Map(
                'sourcedir/*',
                'targetdir',
                sprintf('%s/source', $this->root),
                sprintf('%s/destination', $this->root)
            ),
        );
        $mappings = new MapCollection($mappings);

        $resolvedMappings = $this->globResolver->resolve($mappings);
        $this->assertContainsOnlyInstancesOf('\MagentoHackathon\Composer\Magento\Map\Map', $resolvedMappings);
        $this->assertCount(2, $resolvedMappings);

        $expected = array(
            array(
                'sourcedir/test1.xml',
                'targetdir/test1.xml',
                sprintf('%s/source/sourcedir/test1.xml', $this->root),
                sprintf('%s/destination/targetdir/test1.xml', $this->root)
            ),
            array(
                'sourcedir/test2.xml',
                'targetdir/test2.xml',
                sprintf('%s/source/sourcedir/test2.xml', $this->root),
                sprintf('%s/destination/targetdir/test2.xml', $this->root)
            ),
        );

        $maps = $resolvedMappings->all();

        $this->assertSame($expected[0], $this->mapToArray($maps[0]));
        $this->assertSame($expected[1], $this->mapToArray($maps[1]));
    }

    public function testMappingReturnedAsIsIfNoResultsFromGlob()
    {
        mkdir(sprintf('%s/source/sourcedir', $this->root), 0777, true);

        $mappings = array(
            new Map(
                'sourcedir/*',
                'targetdir',
                sprintf('%s/source', $this->root),
                sprintf('%s/destination', $this->root)
            ),
        );
        $collection = new MapCollection($mappings);

        $resolved = $this->globResolver->resolve($collection)->all();
        $this->assertSame($mappings[0], $resolved[0]);
    }

    public function testIfMappingIsAFileMappingIsReturnedAsIs()
    {
        mkdir(sprintf('%s/source/sourcedir', $this->root), 0777, true);
        touch(sprintf('%s/source/sourcedir/test1.xml', $this->root));
        touch(sprintf('%s/source/sourcedir/test2.xml', $this->root));

        $mappings = array(
            new Map(
                'sourcedir/test1.xml',
                'targetdir',
                sprintf('%s/source', $this->root),
                sprintf('%s/destination', $this->root)
            ),
        );
        $mappings = new MapCollection($mappings);

        $resolvedMappings = $this->globResolver->resolve($mappings);
        $this->assertContainsOnlyInstancesOf('\MagentoHackathon\Composer\Magento\Map\Map', $resolvedMappings);
        $this->assertCount(1, $resolvedMappings);

        $expected = array(
            array(
                'sourcedir/test1.xml',
                'targetdir',
                sprintf('%s/source/sourcedir/test1.xml', $this->root),
                sprintf('%s/destination/targetdir', $this->root)
            ),
        );

        $maps = $resolvedMappings->all();
        $this->assertSame($expected[0], $this->mapToArray($maps[0]));
    }

    /**
     * Helper function to convert map to an array
     *
     * @param Map $map
     * @return array
     */
    protected function mapToArray(Map $map)
    {
        return array(
            $map->getSource(),
            $map->getDestination(),
            $map->getAbsoluteSource(),
            $map->getAbsoluteDestination()
        );
    }

    public function tearDown()
    {
        $this->fileSystem->remove($this->root);
    }
}
