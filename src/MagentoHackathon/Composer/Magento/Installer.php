<?php

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class Installer
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class Installer
{

    /**
     * @var InstallStrategy\InstallStrategyInterface
     */
    protected $installStrategy;

    /**
     * @var array
     */
    protected $mappings;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $sourceRoot;

    /**
     * @var string
     */
    protected $destinationRoot;

    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @var array
     */
    protected $filesToIgnore;

    /**
     * @param InstallStrategyInterface $installStrategy
     * @param FileSystem $fileSystem
     * @param array $mappings
     * @param string $sourceRoot
     * @param string $destinationRoot
     * @param array $options
     * @param array $filesToIgnore
     */
    public function __construct(
        InstallStrategyInterface $installStrategy,
        FileSystem $fileSystem,
        array $mappings,
        $sourceRoot,
        $destinationRoot,
        array $options = array(),
        array $filesToIgnore = array()
    ) {
        $this->installStrategy  = $installStrategy;
        $this->mappings         = $mappings;
        $this->sourceRoot       = $sourceRoot;
        $this->destinationRoot  = $destinationRoot;
        $this->options          = $options;
        $this->fileSystem       = $fileSystem;
        $this->filesToIgnore    = $filesToIgnore;
    }

    /**
     * Delegate installation to the particular strategy
     *
     * @return array
     */
    public function install()
    {
        $createdFiles = array();
        foreach ($this->mappings as $mapping) {
            list ($source, $destination) = $mapping;

            $createdFiles = array_merge(
                $createdFiles,
                $this->create($source, $destination)
            );
        }

        return $this->processCreatedFiles($createdFiles);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return array
     * @throws \ErrorException
     */
    public function create($source, $destination)
    {
        if ($this->isDestinationIgnored($destination)) {
            return;
        }

        $sourceAbsolutePath         = sprintf('%s/%s', $this->getSourceDir(), $this->removeLeadingSlash($source));
        $destinationAbsolutePath    = sprintf('%s/%s', $this->getDestDir(), $this->removeLeadingSlash($destination));

        // Create target directory if it ends with a directory separator
        if ($this->fileSystem->endsWithDirectorySeparator($destination)
            && !is_dir($sourceAbsolutePath)
            && !file_exists($destinationAbsolutePath)
        ) {
            mkdir($destinationAbsolutePath, 0777, true);
        }

        if (!file_exists($sourceAbsolutePath)) {
            // Source file isn't a valid file or glob
            throw new \ErrorException(sprintf('Source %s does not exist', $source));
        }

        $createdFiles = $this->installStrategy->create(
            $sourceAbsolutePath,
            $destinationAbsolutePath,
            $this->options['is_forced']
        );

        if (!is_array($createdFiles)) {
            throw new \ErrorException(
                sprintf(
                    'Install strategy create method should return an array. Got: "%s"',
                    is_object($createdFiles) ? get_class($createdFiles) : gettype($createdFiles)
                )
            );
        }

        return $createdFiles;
    }

    /**
     * Strip off the destination root from the created
     * files
     *
     * @param array $createdFiles
     * @return array
     */
    public function processCreatedFiles(array $createdFiles)
    {
        $fileSystem         = $this->fileSystem;
        $destinationRoot    = $this->destinationRoot;
        return array_map(
            function ($absoluteFilePath) use ($fileSystem, $destinationRoot) {
                return $fileSystem->makePathRelative($absoluteFilePath, $destinationRoot);
            },
            $createdFiles
        );
    }
}
