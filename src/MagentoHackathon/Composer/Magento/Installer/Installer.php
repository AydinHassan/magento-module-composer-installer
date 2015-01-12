<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface;
use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\Map\Map;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use MagentoHackathon\Composer\Magento\Parser\Parser;
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
     * @var GlobResolver
     */
    protected $globResolver;

    /**
     * @var TargetFilter
     */
    protected $targetFilter;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param InstallStrategyFactory $installStrategyFactory
     * @param FileSystem             $fileSystem
     * @param ProjectConfig          $projectConfig
     * @param GlobResolver           $globResolver
     * @param TargetFilter           $targetFilter
     * @param Parser                 $parser
     * @param EventManager           $eventManager
     */
    public function __construct(
        InstallStrategyFactory $installStrategyFactory,
        FileSystem $fileSystem,
        ProjectConfig $projectConfig,
        GlobResolver $globResolver,
        TargetFilter $targetFilter,
        Parser $parser,
        EventManager $eventManager
    ) {
        $this->installStrategyFactory   = $installStrategyFactory;
        $this->fileSystem               = $fileSystem;
        $this->projectConfig            = $projectConfig;
        $this->globResolver             = $globResolver;
        $this->targetFilter             = $targetFilter;
        $this->parser                   = $parser;
        $this->eventManager             = $eventManager;
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
        $mappings        = $this->parser->getMappings($package, $packageSourceDirectory, $this->projectConfig->getMagentoRootDir());

        //lets expand glob mappings first
        $mappings = $this->globResolver->resolve($package, $packageSourceDirectory, $mappings);

        $this->eventManager->dispatch(new PreMappingsResolveEvent($mapCollection));

        $this->prepareInstall($mappings);

        $createdFiles = array();

        foreach ($mappings as $map) {

            $resolvedMappings = $installStrategy->resolve($map);

            $mapsToInsert = array();
            foreach ($resolvedMappings as $resolvedMap) {
                $mapsToInsert[] = new Map($resolvedMap[0], $resolvedMap[1], $packageSourceDirectory, $this->projectConfig->getMagentoRootDir());
            }

            $mappings->replace($map, $mapsToInsert);
        }

        //$this->eventManager->dispatch(new PreMappingsCreate($mapCollection))





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
     * @param MapCollection $mappings
     */
    public function prepareInstall(MapCollection $mappings)
    {
        foreach ($mappings as $map) {
            /** @var Map $map */

            // Create target directory if it ends with a directory separator
            if ($this->fileSystem->endsWithDirectorySeparator($map->getRawDestination())
                && !is_dir($map->getAbsoluteSource())
                && !file_exists($map->getAbsoluteDestination())
            ) {
                $this->fileSystem->ensureDirectoryExists($map->getAbsoluteDestination());
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
