<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Util\FileSystem;
use org\bovigo\vfs\vfsStream;

/**
 * Class LinkTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 */
class LinkTest extends AbstractStrategyTest
{
    protected $root;
    protected $virtualSource;
    protected $virtualDestination;

    public function setUp()
    {
        parent::setup();

        //using vfsstream here as directory iteration yields different order of results on different os's
        $this->root                 = vfsStream::setup('root');
        $this->virtualSource        = sprintf('%s/source', vfsStream::url('root'), $this->getName(false));
        $this->virtualDestination   = sprintf('%s/destination', vfsStream::url('root'), $this->getName(false));

        mkdir($this->virtualSource);
        mkdir($this->virtualDestination);

        $this->assertInstanceOf(
            'MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface',
            new Link(new FileSystem)
        );
    }

    public function testIfFileExistsAtDestinationExceptionIsThrownIfNotForce()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $link = new Link($fileSystem);

        $source = sprintf('%s/local.xml', $this->source);
        $destination = sprintf('%s/local.xml', $this->destination);

        $this->createFileStructure(array('local.xml'), $this->destination);

        $this->setExpectedException(
            'MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException'
        );

        $link->create($source, $destination, false);
        $this->assertFileExists($destination);
    }

    public function testIfFileExistsAtDestinationItIsRemovedIfForceSpecified()
    {
        $fileSystem = $this->getMock('MagentoHackathon\Composer\Magento\Util\FileSystem');
        $link = new Link($fileSystem);

        $source = sprintf('%s/local.xml', $this->source);
        $destination = sprintf('%s/local.xml', $this->destination);

        $this->createFileStructure(array('local.xml'), $this->source);
        $this->createFileStructure(array('local.xml'), $this->destination);

        $fileSystem
            ->expects($this->once())
            ->method('remove')
            ->with($destination)
            ->will($this->returnCallback(function() use ($destination) {
                unlink($destination);
            }));

        $link->create($source, $destination, true);
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

        $expectedMappings = $this->applyRootDirectoryToExpectedMappings(
            $expectedMappings,
            $this->virtualSource,
            $this->virtualDestination
        );

        $this->createFileStructure($sourceFileStructure, $this->virtualSource);
        $this->createFileStructure($destinationFileStructure, $this->virtualDestination);

        $resolvedMapping = $link->resolve($mapping[0], $mapping[1]);

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
                        '%s/folder/local.xml',
                        '%s/destination-folder/local.xml',
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
                        '%s/folder/child-folder/local.xml',
                        '%s/destination-folder/child-folder/local.xml',
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
                        '%s/folder/child-folder/local.xml',
                        '%s/destination-folder/local.xml',
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
                        '%s/folder/local.xml',
                        '%s/destination-folder/local.xml',
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
                    '%s/folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder/local.xml',
                        '%s/destination-folder/local.xml',
                    ),
                    array(
                        '%s/folder/child-dir/file2.txt',
                        '%s/destination-folder/child-dir/file2.txt',
                    ),
                    array(
                        '%s/folder/child-dir/file3.txt',
                        '%s/destination-folder/child-dir/file3.txt',
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
                    '%s/folder',
                    '%s/destination-folder',
                ),
                'expectedMappings' => array(
                    array(
                        '%s/folder/local.xml',
                        '%s/destination-folder/folder/local.xml',
                    )
                ),
            ),
        );
    }
}
