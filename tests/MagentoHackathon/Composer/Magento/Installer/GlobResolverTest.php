<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Util\Filesystem;
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

        $expected = array(
            array('1.php', '1.php'),
            array('2.php', '2.php'),
            array('directory/1.txt', 'dir/1.txt'),
            array('directory/2.txt', 'dir/2.txt'),
        );

        $resolvedMappings = $this->globResolver->resolve(sprintf('%s/source', $this->root), $mappings);
        $this->assertSame($expected, $resolvedMappings);
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
