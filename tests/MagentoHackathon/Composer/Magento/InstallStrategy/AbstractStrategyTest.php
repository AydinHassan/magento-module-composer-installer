<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Util\FileSystem;
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
     * Setup
     */
    public function setup()
    {
        $this->fileSystem   = new FileSystem;
        $this->source       = sprintf('%s/%s/source', sys_get_temp_dir(), $this->getName(false));
        $this->destination  = sprintf('%s/%s/destination', sys_get_temp_dir(), $this->getName(false));

        $this->fileSystem->ensureDirectoryExists($this->source);
        $this->fileSystem->ensureDirectoryExists($this->destination);
    }

    /**
     * @param array $mapping
     * @return array
     */
    public function applyRootDirectoryToMapping(array $mapping)
    {
        return array(
            sprintf($mapping[0], $this->source),
            sprintf($mapping[1], $this->destination),
        );
    }

    /**
     * @param array $mappings
     * @return array
     */
    public function applyRootDirectoryToExpectedMappings(array $mappings)
    {
        $that = $this;
        return array_map(
            function (array $mapping) use ($that) {
                return $that->applyRootDirectoryToMapping($mapping);
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
