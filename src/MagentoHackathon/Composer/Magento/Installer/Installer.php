<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface;
use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\SourceNotExistsException;
use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;
use MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface;
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
     * @var ProjectConfig
     */
    protected $config;

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
     * @param ProjectConfig          $config
     * @param GlobResolver           $globResolver
     * @param TargetFilter           $targetFilter
     * @param Parser                 $parser
     * @param EventManager           $eventManager
     */
    public function __construct(
        InstallStrategyFactory $installStrategyFactory,
        FileSystem $fileSystem,
        ProjectConfig $config,
        GlobResolver $globResolver,
        TargetFilter $targetFilter,
        Parser $parser,
        EventManager $eventManager
    ) {
        $this->installStrategyFactory   = $installStrategyFactory;
        $this->fileSystem               = $fileSystem;
        $this->projectConfig            = $config;
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
        $force           = $this->config->getMagentoForceByPackageName($package->getName());
        $mappings        = $this->parser->getMappings($package, $packageSourceDirectory, $this->config->getMagentoRootDir());

        //lets expand glob mappings first
        $mappings = $this->globResolver->resolve($package, $packageSourceDirectory, $mappings);

        //$this->eventManager->dispatch(new PreMappingsResolveEvent($mapCollection));

        $this->prepareInstall($mappings);
        $mappings = $this->resolveMappings($mappings, $installStrategy);

        //$this->eventManager->dispatch(new PreMappingsCreate($mapCollection))

        //remove ignored mappings
        $targetFilter = $this->targetFilter;
        $mappings = $mappings->filter(function (Map $map) use ($package, $targetFilter) {
            return !$targetFilter->isTargetIgnored($package, $map->getDestination());
        });

        $missingSourceFiles = array_filter(
            $mappings,
            function (Map $map) {
                return !file_exists(($map->getAbsoluteSource()));
            }
        );

        //throw exceptions for missing source?

        foreach ($mappings as $map) {
            /** @var Map $map */
            $this->fileSystem->ensureDirectoryExists(dirname($map->getAbsoluteDestination()));
            try {
                $installStrategy->create($map, $force);
            } catch (TargetExistsException $e) {
                //dispath event so console can log?
                //re-throw for now
                throw $e;
            }
        }

        return $mappings;
    }

    /**
     * If raw Map destination ends with a directory separator,
     * source is not a directory and the destination file does not exist
     * create destination s a directory
     *
     * @param MapCollection $mappings
     */
    protected function prepareInstall(MapCollection $mappings)
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
     * @param MapCollection            $mappings
     * @param InstallStrategyInterface $installStrategy
     *
     * @return MapCollection
     */
    protected function resolveMappings(MapCollection $mappings, InstallStrategyInterface $installStrategy)
    {
        $replacementItems = array();
        foreach ($mappings as $map) {
            /** @var Map $map */
            $resolvedMappings = $installStrategy->resolve(
                $map->getSource(),
                $map->getDestination(),
                $map->getAbsoluteSource(),
                $map->getAbsoluteDestination()
            );

            $maps = array_map(
                function (array $mapping) {
                    return new Map($mapping[0], $mapping[1], $mapping[2], $mapping[3]);
                },
                $resolvedMappings
            );

            $replacementItems = array_merge($replacementItems, $maps);
        }

        return new MapCollection($replacementItems);
    }
}
