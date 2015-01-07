<?php
namespace MagentoHackathon\Composer\Magento\InstallStrategy;

class SymlinkTest extends AbstractTest
{
    /**
     * @param string $src
     * @param string $dest
     * @return Symlink
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Symlink($src, $dest);
    }

    /**
     * @param bool $isDir
     * @return string
     */
    public function getTestDeployStrategyFiletype($isDir = false)
    {
        return self::TEST_FILETYPE_LINK;
    }

    public function testClean()
    {
        $src = 'local1.xml';
        $dest = 'local2.xml';
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $src);
        $this->assertTrue(is_readable($this->sourceDir . DIRECTORY_SEPARATOR . $src));
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));

        $globExpander = new GlobExpander(
            $this->sourceDir,
            $this->destDir,
            array(array($src, $dest))
        );
        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();
        $this->assertTrue(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
        unlink($this->destDir . DIRECTORY_SEPARATOR . $dest);
        $this->strategy->clean($this->destDir . DIRECTORY_SEPARATOR . $dest);
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
    }

    public function testChangeLink()
    {
        $wrongFile = $this->sourceDir . DS . 'wrong';
        $rightFile = $this->sourceDir . DS . 'right';
        $link = $this->destDir . DS . 'link';

        touch($wrongFile);
        touch($rightFile);
        @unlink($link);

        symlink($wrongFile, $link);
        $this->assertEquals($wrongFile, readlink($link));

        $globExpander = new GlobExpander(
            $this->sourceDir,
            $this->destDir,
            array(array(basename($rightFile), basename($link)))
        );

        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();

        $this->assertEquals(realpath($rightFile), realpath(dirname($rightFile) . DS . readlink($link)));
    }

    public function testTargetDirWithChildDirExists()
    {
        $globSource = 'sourcedir/childdir';
        $sourceContents = "$globSource/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        $this->mkdir($this->sourceDir . DS . $globSource);
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "targetdir"; // this dir should contain the target child dir
        $this->mkdir($this->destDir . DS . $dest);
        $this->mkdir($this->destDir . DS . $dest . DS . basename($globSource));

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource) . DS . basename($sourceContents);

        $this->strategy->setIsForced(false);
        $this->setExpectedException('ErrorException', "Target targetdir/childdir already exists");

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array($globSource, $dest)));
        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();
    }

    public function testTargetDirWithChildDirExistsForce()
    {
        $globSource = 'sourcedir/childdir';
        $sourceContents = "$globSource/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        $this->mkdir($this->sourceDir . DS . $globSource);
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "targetdir"; // this dir should contain the target child dir
        $this->mkdir($this->destDir . DS . $dest);
        $this->mkdir($this->destDir . DS . $dest . DS . basename($globSource));

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource) . DS . basename($sourceContents);

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array($globSource, $dest)));
        $this->strategy->setIsForced(true);
        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();

        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, self::TEST_FILETYPE_FILE);
        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_LINK);
    }

    /**
     * @see https://github.com/magento-hackathon/magento-composer-installer/issues/121
     */
    public function testEmptyDirectoryCleanup()
    {
        $directory  = '/app/code/Jay/Ext1';
        $file       = $directory . '/file.txt';
        $this->mkdir($this->sourceDir . $directory);
        touch($this->sourceDir . $file);

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array($file, $file)));
        $this->strategy->setMappings($globExpander->expand());
        
        $this->strategy->deploy();
        
        $this->assertFileExists($this->destDir . $file);

        $this->strategy->clean();
        
        $this->assertFileNotExists($this->destDir . $file);
        $this->assertFileNotExists($this->destDir . $directory);
    }

    public function testDeployedFilesAreStored()
    {
        $src = 'local1.xml';
        $dest = 'local2.xml';
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $src);
        $this->assertTrue(is_readable($this->sourceDir . DIRECTORY_SEPARATOR . $src));
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array($src, $dest)));
        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();
        $this->assertTrue(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
        unlink($this->destDir . DIRECTORY_SEPARATOR . $dest);
        $this->strategy->clean($this->destDir . DIRECTORY_SEPARATOR . $dest);
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));

        $this->assertSame(
            array('/local2.xml'),
            $this->strategy->getDeployedFiles()
        );
    }

    public function testGlobFileResultsDoNotContainDoubleSlashesWhenDestinationDirectoryExists()
    {
        $this->mkdir(sprintf('%s/app/etc/modules/', $this->sourceDir));
        $this->mkdir(sprintf('%s/app/etc/modules', $this->destDir));
        touch(sprintf('%s/app/etc/modules/EcomDev_PHPUnit.xml', $this->sourceDir));
        touch(sprintf('%s/app/etc/modules/EcomDev_PHPUnitTest.xml', $this->sourceDir));

        $globExpander = new GlobExpander(
            $this->sourceDir,
            $this->destDir,
            array(array('/app/etc/modules/*.xml', '/app/etc/modules/'))
        );
        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();

        $expected = array(
            '/app/etc/modules/EcomDev_PHPUnit.xml',
            '/app/etc/modules/EcomDev_PHPUnitTest.xml',
        );

        $this->assertEquals($expected, $this->strategy->getDeployedFiles());
    }
}
