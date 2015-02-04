<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

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

        $map = new Map('source', 'destination', $this->source, $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('createSymlink')
            ->with($map->getAbsoluteSource(), $map->getAbsoluteDestination());

        $symlink->create($map, false);
    }

    public function testIfDestinationDirectoryExistsAndIsSameBaseAsSourceExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $map = new Map('source', 'source', $this->source, $this->destination);

        $this->createFileStructure(['source/'], $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('sourceAndDestinationBaseMatch')
            ->with($map->getSource(), $map->getDestination())
            ->will($this->returnValue(true));

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $symlink->create($map, false);
    }

    public function testIfDestinationDirectoryExistsAndIsSameBaseAsSourceItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $map = new Map('source', 'source', $this->source, $this->destination);

        $this->createFileStructure(['source/'], $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('sourceAndDestinationBaseMatch')
            ->with($map->getSource(), $map->getDestination())
            ->will($this->returnValue(true));

        $destination = $map->getAbsoluteDestination();
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
            ->with($map->getAbsoluteSource(), $map->getAbsoluteDestination());

        $symlink->create($map, true);
    }

    public function testIfSymLinkExistsAndIsCorrectReturnEarly()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $map = new Map('source', 'source', $this->source, $this->destination);

        symlink($map->getAbsoluteSource(), $map->getAbsoluteDestination());

        $fileSystem
            ->expects($this->once())
            ->method('symLinkPointsToCorrectLocation')
            ->with($map->getAbsoluteDestination(), $map->getAbsoluteSource())
            ->will($this->returnValue(true));

        $fileSystem
            ->expects($this->never())
            ->method('createSymlink');

        $this->assertNull($symlink->create($map, false));
    }

    public function testIfSymLinkExistsAndIsInCorrectItIsRemovedFirst()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $map = new Map('source', 'source', $this->source, $this->destination);

        $otherFile = sprintf('%s/someOtherFile', $this->source);

        touch($otherFile);
        symlink($otherFile, $map->getAbsoluteDestination());

        $fileSystem
            ->expects($this->once())
            ->method('symLinkPointsToCorrectLocation')
            ->with($map->getAbsoluteDestination(), $map->getAbsoluteSource())
            ->will($this->returnValue(false));

        $destination = $map->getAbsoluteDestination();
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
            ->with($map->getAbsoluteSource(), $map->getAbsoluteDestination());

        $symlink->create($map, false);
    }

    public function testIfFileExistsAtDestinationExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $map = new Map('source', 'source', $this->source, $this->destination);

        $this->createFileStructure(['source'], $this->destination);

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $symlink->create($map, false);
    }

    public function testIfFileExistsAtDestinationItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $symlink = new Symlink($fileSystem);

        $map = new Map('source', 'source', $this->source, $this->destination);

        $this->createFileStructure(['source'], $this->source);
        $this->createFileStructure(['source'], $this->destination);

        $destination = $map->getAbsoluteDestination();
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
            ->with($map->getAbsoluteSource(), $map->getAbsoluteDestination());

        $symlink->create($map, true);
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

        $this->createFileStructure($sourceFileStructure, $this->source);
        $this->createFileStructure($destinationFileStructure, $this->destination);

        $resolvedMapping = $symlink->resolve($mapping[0], $mapping[1], $mapping[2], $mapping[3]);

        $this->assertEquals($expectedMappings, $resolvedMapping);
    }

    public function mapResolverProvider()
    {
        return [
            'file-to-file' => [
                'sourceFileStructure' => [
                    'local1.xml',
                ],
                'destinationFileStructure' => [],
                'mapping' => [
                    'local1.xml',
                    'local2.xml',
                ],
                'expectedMappings' => [
                    [
                        'local1.xml',
                        'local2.xml',
                    ]
                ],
            ],
            'dir-to-dir' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                ],
                'destinationFileStructure' => [],
                'mapping' => [
                    'folder',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder',
                        'destination-folder',
                    ]
                ],
            ],
            'file-to-dir' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                ],
                'destinationFileStructure' => [
                    'destination-folder/'
                ],
                'mapping' => [
                    'folder/local.xml',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder/local.xml',
                        'destination-folder/local.xml',
                    ]
                ],
            ],
            'nested-dir-to-dir-destination-dir-exists' => [
                'sourceFileStructure' => [
                    'folder/child-folder/',
                    'folder/child-folder/local.xml',
                ],
                'destinationFileStructure' => [
                    'destination-folder/child-folder/'
                ],
                'mapping' => [
                    'folder/child-folder',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder/child-folder',
                        'destination-folder/child-folder',
                    ]
                ],
            ],
            'nested-dir-to-dir-destination-dir-not-exist' => [
                'sourceFileStructure' => [
                    'folder/child-folder/',
                    'folder/child-folder/local.xml',
                ],
                'destinationFileStructure' => [],
                'mapping' => [
                    'folder/child-folder',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder/child-folder',
                        'destination-folder',
                    ]
                ],
            ],
            'file-to-dir2' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                ],
                'destinationFileStructure' => [
                    'destination-folder/folder/'
                ],
                'mapping' => [
                    'folder/local.xml',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder/local.xml',
                        'destination-folder/local.xml',
                    ]
                ],
            ],
            'dir-to-dir2' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                ],
                'destinationFileStructure' => [],
                'mapping' => [
                    'folder',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder',
                        'destination-folder',
                    ]
                ],
            ],
            'dir-to-dir3' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                ],
                'destinationFileStructure' => [],
                'mapping' => [
                    'folder',
                    'destination-folder/',
                ],
                'expectedMappings' => [
                    [
                        'folder',
                        'destination-folder/',
                    ]
                ],
            ],
            'dir-to-dir-destination-dir-exists' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                ],
                'destinationFileStructure' => [
                    'destination-folder/'
                ],
                'mapping' => [
                    'folder',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder',
                        'destination-folder/folder',
                    ]
                ],
            ],
        ];
    }
}
