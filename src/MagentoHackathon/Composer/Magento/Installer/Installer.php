<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface;
use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Util\FileSystem;

/**
 * Class Installer
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class Installer
{

    /**
     * @var InstallStrategyFactory
     */
    protected $installStrategyFactory;

    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @var array
     */
    protected $filesToIgnore;

    /**
     * @var ProjectConfig
     */
    protected $projectConfig;

    /**
     * @var ParserFactoryInterface
     */
    protected $parserFactory;

    /**
     * @var GlobResolver
     */
    protected $globResolver;

    /**
     * @var TargetFilter
     */
    protected $targetFilter;

    /**
     * @param InstallStrategyFactory $installStrategyFactory
     * @param FileSystem $fileSystem
     * @param ProjectConfig $projectConfig
     * @param ParserFactoryInterface $parserFactory
     * @param GlobResolver $globResolver
     * @param TargetFilter $targetFilter
     */
    public function __construct(
        InstallStrategyFactory $installStrategyFactory,
        FileSystem $fileSystem,
        ProjectConfig $projectConfig,
        ParserFactoryInterface $parserFactory,
        GlobResolver $globResolver,
        TargetFilter $targetFilter
    ) {
        $this->installStrategyFactory   = $installStrategyFactory;
        $this->fileSystem               = $fileSystem;
        $this->projectConfig            = $projectConfig;
        $this->parserFactory            = $parserFactory;
        $this->globResolver             = $globResolver;
        $this->targetFilter             = $targetFilter;
    }

    /**
     * Delegate installation to the particular strategy
     *
     * @param PackageInterface $package
     * @param string $packageSourceDirectory
     * @return array
     * @throws \ErrorException
     */
    public function install(PackageInterface $package, $packageSourceDirectory)
    {
        $installStrategy = $this->installStrategyFactory->make($package);
        $force           = $this->projectConfig->getMagentoForceByPackageName($package->getName());
        $mapParser       = $this->parserFactory->make($package, $packageSourceDirectory);

        //strip leading slashes from mappings
        $mappings = array_map(
            function ($map) {
                return array(
                    ltrim($map[0], '\\/'),
                    ltrim($map[1], '\\/'),
                );
            },
            $mapParser->getMappings()
        );

        //lets expand glob mappings first
        $mappings = $this->globResolver->resolve($package, $packageSourceDirectory, $mappings);

        $this->prepareInstall($mappings, $packageSourceDirectory, $this->projectConfig->getMagentoRootDir());

        //strip leading slashes from mappings
        $mappings = array_map(
            function ($map) {
                return array(
                    rtrim($map[0], '\\/'),
                    rtrim($map[1], '\\/'),
                );
            },
            $mappings
        );

        $createdFiles = array();
        foreach ($mappings as $mapping) {
            list ($source, $destination) = $mapping;

            $resolvedMappings = $installStrategy->resolve($source, $destination);

            foreach ($resolvedMappings as $mapping) {
                if ($this->targetFilter->isTargetIgnored($package, $mapping[1])) {
                    continue;
                }


            }

            try {
                $createdFiles = array_merge(
                    $createdFiles,
                    $this->create($source, $destination)
                );
            } catch (TargetExistsException $e) {
            }
        }

        return $this->processCreatedFiles($createdFiles);
    }

    /**
     * @param array $mappings
     * @param string $sourceRoot
     * @param string $destinationRoot
     */
    public function prepareInstall(array $mappings, $sourceRoot, $destinationRoot)
    {
        foreach ($mappings as $mapping) {
            list ($source, $destination) = $mapping;
            $sourceAbsolutePath         = sprintf('%s/%s', $sourceRoot, $source);
            $destinationAbsolutePath    = sprintf('%s/%s', $destinationRoot, $destination);

            // Create target directory if it ends with a directory separator
            if ($this->fileSystem->endsWithDirectorySeparator($destinationAbsolutePath)
                && !is_dir($sourceAbsolutePath)
                && !file_exists($destinationAbsolutePath)
            ) {
                $this->fileSystem->ensureDirectoryExists($destinationAbsolutePath);
            }
        }
    }

    /**
     * @param string $source
     * @param string $destination
     * @return array
     * @throws \ErrorException
     */
    public function create($source, $destination)
    {

        $sourceAbsolutePath         = sprintf('%s/%s', $this->getSourceDir(), $this->removeLeadingSlash($source));
        $destinationAbsolutePath    = sprintf('%s/%s', $this->getDestDir(), $this->removeLeadingSlash($destination));

        // Create target directory if it ends with a directory separator
        if ($this->fileSystem->endsWithDirectorySeparator($destination)
            && !is_dir($sourceAbsolutePath)
            && !file_exists($destinationAbsolutePath)
        ) {
            mkdir($destinationAbsolutePath, 0777, true);
            $destinationAbsolutePath = rtrim($destinationAbsolutePath, '\\/');
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
