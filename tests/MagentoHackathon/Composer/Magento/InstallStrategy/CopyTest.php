<?php
namespace MagentoHackathon\Composer\Magento\InstallStrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param string $src
     * @param string $dest
     * @return Copy
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Copy($src, $dest);
    }

    /**
     * @param bool $isDir
     * @return string
     */
    public function getTestDeployStrategyFiletype($isDir = false)
    {
        if ($isDir) {
            return self::TEST_FILETYPE_DIR;
        }

        return self::TEST_FILETYPE_FILE;
    }
    
    public function testCopyDirToDirOfSameName()
    {
        $sourceRoot = 'root';
        $sourceContents = "subdir/subdir/test.xml";

        $this->mkdir(sprintf('%s/%s/subdir/subdir', $this->sourceDir, $sourceRoot));
        touch(sprintf('%s/%s/subdir/subdir/test.xml', $this->sourceDir, $sourceRoot));

        // intentionally using a differnt name to verify solution doesn't rely on identical src/dest paths
        $dest = "dest/root";
        $this->mkdir(sprintf('%s/%s', $this->destDir, $dest));
        $testTarget = sprintf('%s/%s/%s', $this->destDir, $dest, $sourceContents);

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array($sourceRoot, $dest)));
        $this->strategy->setMappings($globExpander->expand());

        $this->strategy->deploy();
        $this->assertFileExists($testTarget);

        $this->strategy->setIsForced(true);
        $this->strategy->deploy();

        $this->assertFileNotExists(dirname(dirname($testTarget)) . DS . basename($testTarget));
    }

    public function testWildcardCopyToExistingDir()
    {
        $file1 = "app/code/test.php";
        
        //create target directory before
        $this->mkdir(sprintf('%s/app/code', $this->destDir));
        $this->mkdir(sprintf('%s/app/code', $this->sourceDir));

        touch(sprintf('%s/%s', $this->sourceDir, $file1));

        $this->mkdir(sprintf('%s/dest/dir', $this->destDir));
        $testTarget = sprintf('%s/%s', $this->destDir, $file1);

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array('*', '/')));
        $this->strategy->setMappings($globExpander->expand());

        $this->strategy->deploy();
        $this->assertFileExists($testTarget);

        $this->strategy->setIsForced(true);
        $this->strategy->deploy();

        $this->assertFileNotExists(sprintf('%s/app/app/code/test.php', $this->destDir));
    }

//    public function testDeployedFilesAreStored()
//    {
//        $sourceRoot = 'root';
//        $sourceContents = "subdir/subdir/test.xml";
//
//        $this->mkdir($this->sourceDir . DS . $sourceRoot . DS . dirname($sourceContents));
//        touch($this->sourceDir . DS . $sourceRoot . DS . $sourceContents);
//
//        // intentionally using a differnt name to verify solution doesn't rely on identical src/dest paths
//        $dest = "dest/root";
//        $this->mkdir($this->destDir . DS . $dest);
//
//        $testTarget = $this->destDir . DS . $dest . DS . $sourceContents;
//        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, array(array($sourceRoot, $dest)));
//        $this->strategy->setMappings($globExpander->expand());
//
//        $this->strategy->deploy();
//        $this->assertFileExists($testTarget);
//
//        $this->assertFileNotExists(dirname(dirname($testTarget)) . DS . basename($testTarget));
//
//        $this->assertSame(
//            array('/dest/root/subdir/subdir/test.xml'),
//            $this->strategy->getDeployedFiles()
//        );
//    }

    public function testIfDestinationIsDirectoryAndSourceAndDestinationAreDifferentNamesSourceIsPlacedInsideDestination()
    {
        $this->mkdir(sprintf('%s/app', $this->sourceDir));
        touch(sprintf('%s/app/Some_Module.xml', $this->sourceDir));
        $this->mkdir(sprintf('%s/app/etc/modules', $this->destDir));

        $mappings = array(
            array('app/Some_Module.xml', 'app/etc/modules')
        );

        $globExpander = new GlobExpander($this->sourceDir, $this->destDir, $mappings);
        $this->strategy->setMappings($globExpander->expand());
        $this->strategy->deploy();

        $this->assertFileExists(sprintf('%s/app/etc/modules/Some_Module.xml', $this->destDir));
        $this->assertFileType(sprintf('%s/app/etc/modules/Some_Module.xml', $this->destDir), self::TEST_FILETYPE_FILE);
    }
}
