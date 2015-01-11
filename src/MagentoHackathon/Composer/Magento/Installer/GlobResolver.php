<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\SourceNotExistsException;

/**
 * Simple class to expand glob mappings to simple file mappings
 *
 * Class GlobExpander
 * @package MagentoHackathon\Composer\Magento\InstallStrategyOld
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
final class GlobResolver
{

    /**
     * Resolve glob mappings to file -> file mappings
     *
     * @param string $source
     * @param array $mappings
     *
     * @return array
     */
    public function resolve($source, array $mappings)
    {
        //enforce type safety - each record should be an array
        array_map(function (array $map) {
        }, $mappings);


        $updatedMappings = array();
        foreach ($mappings as $mapping) {
            $relativeSource         = ltrim($mapping[0], '\\/');
            $relativeDestination    = trim($mapping[1], '\\/');
            $absoluteSource         = sprintf('%s/%s', $source, $relativeSource);

            if (file_exists($absoluteSource)) {
                //file is a file, we don't care about this
                $updatedMappings[] = $mapping;
                continue;
            }

            //not a file, is it a glob?
            $iterator = new \GlobIterator($absoluteSource, \FilesystemIterator::KEY_AS_FILENAME);

            if (!$iterator->count()) {
                //maybe this error is wrong, as it could be a valid glob, just there were no results.
                throw new SourceNotExistsException($absoluteSource);
            }

            //add each glob as a separate mapping
            foreach ($iterator as $file) {
                $updatedMappings[] = $this->processMapping($file, $relativeDestination, $source);
            }
        }

        return $updatedMappings;
    }

    /**
     * @param \SplFileInfo $globMatch
     * @param string $relativeDestination
     * @return array
     */
    protected function processMapping(\SplFileInfo $globMatch, $relativeDestination, $sourceRoot)
    {
        $absolutePath = $globMatch->getPathname();

        //get the relative path to this file/dir - strip of the source path
        //+1 to strip leading slash
        $source = substr($absolutePath, strlen($sourceRoot) + 1);

        if ($globMatch->isDir()) {
            $destination = ltrim(sprintf('%s/%s', $relativeDestination, $source), '\\/');
        } else {
            $destination = ltrim(sprintf('%s/%s', $relativeDestination, $globMatch->getFilename()), '\\/');
        }

        return array($source, $destination);
    }
}
