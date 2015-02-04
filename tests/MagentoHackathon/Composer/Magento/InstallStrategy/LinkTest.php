<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class LinkTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 */
class LinkTest extends AbstractStrategyTest
{
    public function setUp()
    {
        parent::setup();
        $this->assertInstanceOf(
            'MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface',
            new Link(new FileSystem)
        );
    }

    public function testIfFileExistsAtDestinationExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $link = new Link($fileSystem);

        $map = new Map('local.xml', 'local.xml', $this->source, $this->destination);

        $this->createFileStructure(array('local.xml'), $this->destination);

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $link->create($map, false);
        $this->assertFileExists($map->getAbsoluteDestination());
    }

    public function testIfFileExistsAtDestinationItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $link = new Link($fileSystem);

        $map = new Map('local.xml', 'local.xml', $this->source, $this->destination);

        $this->createFileStructure(array('local.xml'), $this->source);
        $this->createFileStructure(array('local.xml'), $this->destination);

        $destination = $map->getAbsoluteDestination();
        $fileSystem
            ->expects($this->once())
            ->method('remove')
            ->with($destination)
            ->will($this->returnCallback(function() use ($destination) {
                unlink($destination);
            }));

        $link->create($map, true);
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
        $link = new Link(new FileSystem);

        $mapping = $this->applyRootDirectoryToMapping($mapping, $this->virtualSource, $this->virtualDestination);

        $this->createFileStructure($sourceFileStructure, $this->virtualSource);
        $this->createFileStructure($destinationFileStructure, $this->virtualDestination);

        $resolvedMapping = $link->resolve($mapping[0], $mapping[1], $mapping[2], $mapping[3]);
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
                    'local1.xml',
                    'local2.xml',
                ),
                'expectedMappings' => array(
                    array(
                        'local1.xml',
                        'local2.xml',
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
                    'folder',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/local.xml',
                        'destination-folder/local.xml',
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
                    'folder/local.xml',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/local.xml',
                        'destination-folder/local.xml',
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
                    'folder/child-folder',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/child-folder/local.xml',
                        'destination-folder/child-folder/local.xml',
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
                    'folder/child-folder',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/child-folder/local.xml',
                        'destination-folder/local.xml',
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
                    'folder/local.xml',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/local.xml',
                        'destination-folder/local.xml',
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
                    'folder',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/local.xml',
                        'destination-folder/local.xml',
                    )
                ),
            ),
            'dir-to-dir3' => array(
                'sourceFileStructure' => array(
                    'folder/',
                    'folder/local.xml',
                    'folder/child-dir/',
                    'folder/child-dir/file2.txt',
                    'folder/child-dir/file3.txt',
                ),
                'destinationFileStructure' => array(),
                'mapping' => array(
                    'folder',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/local.xml',
                        'destination-folder/local.xml',
                    ),
                    array(
                        'folder/child-dir/file2.txt',
                        'destination-folder/child-dir/file2.txt',
                    ),
                    array(
                        'folder/child-dir/file3.txt',
                        'destination-folder/child-dir/file3.txt',
                    ),
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
                    'folder',
                    'destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        'folder/local.xml',
                        'destination-folder/folder/local.xml',
                    )
                ),
            ),
        );
    }
}
