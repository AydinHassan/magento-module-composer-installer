<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Util\FileSystem;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

/**
 * Class AbstractStrategyTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 */
abstract class AbstractStrategyTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @var string vfsStream virtual source root
     */
    protected $virtualSource;

    /**
     * @var string vfsStream virtual destination root
     */
    protected $virtualDestination;

    /**
     * Setup
     */
    public function setup()
    {
        $this->fileSystem   = new FileSystem;
        $this->source       = sprintf('%s/%s/source', sys_get_temp_dir(), $this->getName(false));
        $this->destination  = sprintf('%s/%s/destination', sys_get_temp_dir(), $this->getName(false));

        $this->fileSystem->ensureDirectoryExists($this->source);
        $this->fileSystem->ensureDirectoryExists($this->destination);

        //using vfsstream here as directory iteration yields different order of results on different os's
        vfsStream::setup('root');
        $this->virtualSource        = sprintf('%s/source', vfsStream::url('root'), $this->getName(false));
        $this->virtualDestination   = sprintf('%s/destination', vfsStream::url('root'), $this->getName(false));

        $this->fileSystem->ensureDirectoryExists($this->virtualSource);
        $this->fileSystem->ensureDirectoryExists($this->virtualDestination);
    }

    /**
     * @param array         $mapping
     * @param null|string   $source
     * @param null|string   $destination
     *
     * @return array
     */
    public function applyRootDirectoryToMapping(array $mapping, $source = null, $destination = null)
    {
        return [
            $mapping[0],
            $mapping[1],
            ($source ? $source : $this->source) . '/' . $mapping[0],
            ($destination ? $destination : $this->destination) . '/' . $mapping[1],
        ];
    }

    /**
     * @param array         $mappings
     * @param null|string   $source
     * @param null|string   $destination
     *
     * @return array
     */
    public function applyRootDirectoryToExpectedMappings(array $mappings, $source = null, $destination = null)
    {
        $that = $this;
        return array_map(
            function (array $mapping) use ($that, $source, $destination) {
                return $that->applyRootDirectoryToMapping($mapping, $source, $destination);
            },
            $mappings
        );
    }

    /**
     * Simple helper function to build a directory structure, will create files and folders
     * from the given root.
     * Folders are assumed if path ends in '/' otherwise it will be treated as a file
     *
     * @param array $structure
     * @param string $root Where to start from
     */
    protected function createFileStructure(array $structure, $root)
    {
        foreach ($structure as $file) {
            $path = sprintf('%s/%s', $root, $file);
            $this->fileSystem->ensureDirectoryExists(dirname($path));

            if (substr($path, -1) === '/') {
                //assume this is a directory
                mkdir($path);
            } else {
                touch($path);
            }
        }
    }

    /**
     * Cleanup
     */
    public function tearDown()
    {
        $this->fileSystem->remove($this->source);
        $this->fileSystem->remove($this->destination);
    }
}
