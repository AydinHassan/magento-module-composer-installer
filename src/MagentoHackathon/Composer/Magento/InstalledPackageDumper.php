<?php

namespace MagentoHackathon\Composer\Magento;
use MagentoHackathon\Composer\Magento\Map\MapCollection;
use MagentoHackathon\Composer\Magento\Map\Map;

/**
 * Class InstalledPackageDumper
 * @package MagentoHackathon\Composer\Magento
 */
class InstalledPackageDumper
{
    /**
     * @param InstalledPackage $installedPackage
     * @return array
     */
    public function dump(InstalledPackage $installedPackage)
    {
        return array(
            'packageName'       => $installedPackage->getName(),
            'version'           => $installedPackage->getVersion(),
            'installedFiles'    => $installedPackage->getInstalledFiles(),
            'mappings'          => $this->dumpMappings($installedPackage->getMappings()),
        );
    }

    /**
     * @param array $data
     * @return InstalledPackage
     */
    public function restore(array $data)
    {
        return new InstalledPackage($data['packageName'], $data['version'], $this->restoreMappings($data['mappings']));
    }

    /**
     * @param MapCollection $mappings
     *
     * @return array
     */
    private function dumpMappings(MapCollection $mappings)
    {
        return array_map(
            function (Map $map) {
                return array(
                    'source'            => $map->getSource(),
                    'destination'       => $map->getDestination(),
                    'source_root'       => $map->getSourceRoot(),
                    'destination_root'  => $map->getDestinationRoot()
                );
            },
            $mappings->all()
        );
    }

    /**
     * @param array $mappings
     *
     * @return MapCollection
     */
    private function restoreMappings(array $mappings)
    {
        return new MapCollection(array_map(
            function (array $row) {
                return new Map(
                    $row['source'],
                    $row['destination'],
                    $row['source_root'],
                    $row['destination_root']
                );
            },
            $mappings
        ));
    }
}
