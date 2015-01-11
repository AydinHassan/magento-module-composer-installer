<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Util\FileSystem;
use PHPUnit_Framework_TestCase;

/**
 * Class SymlinkTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SymlinkTest extends AbstractStrategyTest
{
    public function setUp()
    {
        parent::setup();
        $this->assertInstanceOf(
            'MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface',
            new Symlink(new FileSystem)
        );
    }

    public function testCreateSymLink()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source = sprintf('%s/source', $this->source);
        $destination = sprintf('%s/destination', $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('createSymlink')
            ->with($source, $destination);

        $symlink->create($source, $destination, false);
    }

    public function testIfDestinationDirectoryExistsAndIsSameBaseAsSourceExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source = sprintf('%s/source', $this->source);
        $destination = sprintf('%s/source', $this->destination);

        $this->createFileStructure(array('source/'), $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('sourceAndDestinationBaseMatch')
            ->with($source, $destination)
            ->will($this->returnValue(true));

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $symlink->create($source, $destination, false);
    }

    public function testIfDestinationDirectoryExistsAndIsSameBaseAsSourceItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source = sprintf('%s/source', $this->source);
        $destination = sprintf('%s/source', $this->destination);

        $this->createFileStructure(array('source/'), $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('sourceAndDestinationBaseMatch')
            ->with($source, $destination)
            ->will($this->returnValue(true));

        $fileSystem
            ->expects($this->once())
            ->method('remove')
            ->with($destination)
            ->will($this->returnCallback(function() use ($destination) {
                rmdir($destination);
            }));


        $fileSystem
            ->expects($this->once())
            ->method('createSymlink')
            ->with($source, $destination);

        $symlink->create($source, $destination, true);
    }

    public function testIfSymLinkExistsAndIsCorrectReturnEarly()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source = sprintf('%s/source', $this->source);
        $destination = sprintf('%s/source', $this->destination);

        symlink($source, $destination);

        $fileSystem
            ->expects($this->once())
            ->method('symLinkPointsToCorrectLocation')
            ->with($destination, $source)
            ->will($this->returnValue(true));

        $this->assertEquals(array(), $symlink->create($source, $destination, false));
    }

    public function testIfSymLinkExistsAndIsInCorrectItIsRemovedFirst()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source         = sprintf('%s/source', $this->source);
        $destination    = sprintf('%s/source', $this->destination);
        $otherFile      = sprintf('%s/someOtherFile', $this->source);

        touch($otherFile);
        symlink($otherFile, $destination);

        $fileSystem
            ->expects($this->once())
            ->method('symLinkPointsToCorrectLocation')
            ->with($destination, $source)
            ->will($this->returnValue(false));

        $fileSystem
            ->expects($this->once())
            ->method('remove')
            ->with($destination)
            ->will($this->returnCallback(function() use ($destination) {
                unlink($destination);
            }));

        $fileSystem
            ->expects($this->once())
            ->method('createSymlink')
            ->with($source, $destination);

        $symlink->create($source, $destination, false);
    }

    public function testIfFileExistsAtDestinationExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source = sprintf('%s/source', $this->source);
        $destination = sprintf('%s/source', $this->destination);

        $this->createFileStructure(array('source'), $this->destination);

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $symlink->create($source, $destination, false);
    }

    public function testIfFileExistsAtDestinationItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $source = sprintf('%s/source', $this->source);
        $destination = sprintf('%s/source', $this->destination);

        $this->createFileStructure(array('source'), $this->source);
        $this->createFileStructure(array('source'), $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('remove')
            ->with($destination)
            ->will($this->returnCallback(function() use ($destination) {
                unlink($destination);
            }));

        $fileSystem
            ->expects($this->once())
            ->method('createSymlink')
            ->with($source, $destination);

        $symlink->create($source, $destination, true);
    }

    /**
     * @dataProvider mapResolverProvider
     *
     * @param array $sourceFileStructure
     * @param array $destinationFileStructure
     * @param array $mapping
     * @param array $expectedMappings
     */
    public function testResolveMappings(
        array $sourceFileStructure,
        array $destinationFileStructure,
        array $mapping,
        array $expectedMappings
    ) {
        $symlink = new Symlink(new FileSystem);

        $mapping = $this->applyRootDirectoryToMapping($mapping);
        $expectedMappings = $this->applyRootDirectoryToExpectedMappings($expectedMappings);

        $this->createFileStructure($sourceFileStructure, $this->source);
        $this->createFileStructure($destinationFileStructure, $this->destination);

        $resolvedMapping = $symlink->resolve($mapping[0], $mapping[1]);

        $this->assertEquals($expectedMappings, $resolvedMapping);
    }

    public function mapResolverProvider()
    {
        return array(
            'file-to-file' => array(
                'sourceFileStructure' => array(
                    'local1.xml',
                ),
                'destinationFileStructure' => array(),
                'mapping' => array(
                    '%s/local1.xml',
                    '%s/local2.xml',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/local1.xml',
                        '%s/local2.xml',
                    )
                ),
            ),
            'dir-to-dir' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                ),
                'destinationFileStructure' => array(),
                'mapping' => array(
                    '%s/folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder',
                        '%s/destination-folder',
                    )
                ),
            ),
            'file-to-dir' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                ),
                'destinationFileStructure' => array(
                    'destination-folder/'
                ),
                'mapping' => array(
                    '%s/folder/local.xml',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder/local.xml',
                        '%s/destination-folder/local.xml',
                    )
                ),
            ),
            'nested-dir-to-dir-destination-dir-exists' => array(
                'sourceFileStructure' => array(
                    'folder/child-folder/',
                    'folder/child-folder/local.xml',
                ),
                'destinationFileStructure' => array(
                    'destination-folder/child-folder/'
                ),
                'mapping' => array(
                    '%s/folder/child-folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder/child-folder',
                        '%s/destination-folder/child-folder',
                    )
                ),
            ),
            'nested-dir-to-dir-destination-dir-not-exist' => array(
                'sourceFileStructure' => array(
                    'folder/child-folder/',
                    'folder/child-folder/local.xml',
                ),
                'destinationFileStructure' => array(),
                'mapping' => array(
                    '%s/folder/child-folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder/child-folder',
                        '%s/destination-folder',
                    )
                ),
            ),
            'file-to-dir2' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                ),
                'destinationFileStructure' => array(
                    'destination-folder/folder/'
                ),
                'mapping' => array(
                    '%s/folder/local.xml',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder/local.xml',
                        '%s/destination-folder/local.xml',
                    )
                ),
            ),
            'dir-to-dir2' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                ),
                'destinationFileStructure' => array(),
                'mapping' => array(
                    '%s/folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder',
                        '%s/destination-folder',
                    )
                ),
            ),
            'dir-to-dir3' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                ),
                'destinationFileStructure' => array(),
                'mapping' => array(
                    '%s/folder',
                    '%s/destination-folder/',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder',
                        '%s/destination-folder/',
                    )
                ),
            ),
            'dir-to-dir-destination-dir-exists' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                ),
                'destinationFileStructure' => array(
                    'destination-folder/'
                ),
                'mapping' => array(
                    '%s/folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder',
                        '%s/destination-folder/folder',
                    )
                ),
            ),
        );
    }
}
