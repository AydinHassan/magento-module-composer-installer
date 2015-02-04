<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class CopyTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 */
class CopyTest extends AbstractStrategyTest
{

    public function setUp()
    {
        parent::setup();
        $this->assertInstanceOf(
            'MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface',
            new Copy(new FileSystem)
        );
    }

    public function testIfFileExistsAtDestinationExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $copy = new Copy($fileSystem);

        $map = new Map('local.xml', 'local.xml', $this->virtualSource, $this->virtualDestination);

        $this->createFileStructure(['local.xml'], $this->virtualDestination);

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $copy->create($map, false);
        $this->assertFileExists($map->getAbsoluteDestination());
    }

    public function testIfFileExistsAtDestinationItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $copy = new Copy($fileSystem);

        $map = new Map('local.xml', 'local.xml', $this->source, $this->destination);

        $this->createFileStructure(['local.xml'], $this->source);
        $this->createFileStructure(['local.xml'], $this->destination);

        $destination = $map->getAbsoluteDestination();
        $fileSystem
            ->expects($this->once())
            ->method('remove')
            ->with($destination)
            ->will($this->returnCallback(function() use ($destination) {
                unlink($destination);
            }));

        $copy->create($map, true);
        $this->assertFileExists($destination);
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
        $copy = new Copy(new FileSystem);
        $mapping = $this->applyRootDirectoryToMapping($mapping, $this->virtualSource, $this->virtualDestination);

        $this->createFileStructure($sourceFileStructure, $this->virtualSource);
        $this->createFileStructure($destinationFileStructure, $this->virtualDestination);

        $resolvedMapping = $copy->resolve($mapping[0], $mapping[1], $mapping[2], $mapping[3]);
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
                        'folder/local.xml',
                        'destination-folder/local.xml',
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
                        'folder/child-folder/local.xml',
                        'destination-folder/child-folder/local.xml',
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
                        'folder/child-folder/local.xml',
                        'destination-folder/local.xml',
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
                        'folder/local.xml',
                        'destination-folder/local.xml',
                    ]
                ],
            ],
            'dir-to-dir3' => [
                'sourceFileStructure' => [
                    'folder/',
                    'folder/local.xml',
                    'folder/child-dir/',
                    'folder/child-dir/file2.txt',
                    'folder/child-dir/file3.txt',
                ],
                'destinationFileStructure' => [],
                'mapping' => [
                    'folder',
                    'destination-folder',
                ],
                'expectedMappings' => [
                    [
                        'folder/local.xml',
                        'destination-folder/local.xml',
                    ],
                    [
                        'folder/child-dir/file2.txt',
                        'destination-folder/child-dir/file2.txt',
                    ],
                    [
                        'folder/child-dir/file3.txt',
                        'destination-folder/child-dir/file3.txt',
                    ],
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
                        'folder/local.xml',
                        'destination-folder/folder/local.xml',
                    ]
                ],
            ],
        ];
    }
}
