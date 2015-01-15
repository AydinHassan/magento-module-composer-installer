<?php
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class FileSystemTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class FileSystemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FileSystem
     */
    protected $fileSystem;

    public function setUp()
    {
        $this->fileSystem = new FileSystem();
    }

    /**
     * @dataProvider relativePathProvider
     *
     * @param string $source
     * @param string $destination
     * @param string $expected
     */
    public function testGetRelativePath($source, $destination, $expected)
    {
        $this->assertSame($expected, $this->fileSystem->getRelativePath($source, $destination));
    }

    public function relativePathProvider()
    {
        return array(
            array('/source/one/two/local.xml', '/destination/one/two/local.xml', '../../../destination/one/two/local.xml'),
            array('local.xml', 'destination.xml', './destination.xml'),
            array('/source/local.xml', '/destination/local.xml', '../destination/local.xml'),
            array('/source/local.xml', '/destination/file.xml', '../destination/file.xml'),
        );
    }


    public function testMakePathRelative()
    {
        $rootDir = '/absolute/path';
        $absolutePath = '/absolute/path/some/file.txt';
        $this->assertSame('some/file.txt', $this->fileSystem->makePathRelative($absolutePath, $rootDir));
    }

    public function testSourceAndDestinationBaseMatch()
    {
        $this->assertTrue($this->fileSystem->sourceAndDestinationBaseMatch('lol/somefile', 'destination/somefile'));
        $this->assertFalse($this->fileSystem->sourceAndDestinationBaseMatch('lol/different', 'destination/somefile'));
    }

    public function testSymLinkPointsToCorrectLocation()
    {
        $root = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        mkdir($root, 0777, true);
        mkdir(sprintf('%s/source', $root));
        mkdir(sprintf('%s/destination', $root));

        $file1  = sprintf('%s/source/file1.txt', $root);
        $link   = sprintf('%s/destination/destination-file.txt', $root);
        touch($file1);
        symlink($file1, $link);

        $this->assertTrue($this->fileSystem->symLinkPointsToCorrectLocation($link, $file1));
        $this->assertFalse($this->fileSystem->symLinkPointsToCorrectLocation($link, 'some/other/location'));
    }

    public function testEndsWithDirectorySeparator()
    {
        $this->assertTrue($this->fileSystem->endsWithDirectorySeparator('some/path/'));
        $this->assertTrue($this->fileSystem->endsWithDirectorySeparator('\some\path\\'));
        $this->assertFalse($this->fileSystem->endsWithDirectorySeparator('\some\path'));
    }

    public function testRemove()
    {
        $root = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        mkdir($root, 0777, true);
        mkdir(sprintf('%s/source', $root));
        mkdir(sprintf('%s/destination', $root));

        $file1  = sprintf('%s/source/file1.txt', $root);
        $link   = sprintf('%s/destination/destination-file.txt', $root);
        touch($file1);
        symlink($file1, $link);

        //remove symlink
        $this->fileSystem->remove($link);
        $this->assertFileNotExists($link);
        $this->assertFileExists($file1);

        //remove file
        $this->fileSystem->remove($file1);
        $this->assertFileNotExists($file1);

        //remove dir
        $this->fileSystem->remove($root);
        $this->assertFileNotExists($root);
    }

    public function testRemoveRetursnsFalseIfNotAFileOrDirectory()
    {
        $this->assertFalse($this->fileSystem->remove('lol'));
    }

    public function testCreateSymLinkSuccessfullyCreatesSymLink()
    {
        $root = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        mkdir($root, 0777, true);
        mkdir(sprintf('%s/source', $root));
        mkdir(sprintf('%s/destination', $root));

        $file1  = sprintf('%s/source/file1.txt', $root);
        $link   = sprintf('%s/destination/destination-file.txt', $root);
        touch($file1);

        $this->fileSystem->createSymlink($file1, $link);
        $this->assertTrue(is_link($link));

        $realPath = realpath(sprintf('%s/destination/%s', $root, readlink($link)));
        $this->assertSame(realpath($file1), $realPath);
    }

    public function testCreateSymLinkThrowsExceptionIfCreationFails()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        $this->setExpectedException('ErrorException');
        $this->fileSystem->createSymlink('lolnotafile', 'lolnotafile');
        unlink('lolnotafile');
        \PHPUnit_Framework_Error_Warning::$enabled = true;
    }

    public function tearDown()
    {
        $fs = new \Composer\Util\Filesystem();
        $fs->remove(sprintf('%s/%s', sys_get_temp_dir(), $this->getName()));
    }
}
