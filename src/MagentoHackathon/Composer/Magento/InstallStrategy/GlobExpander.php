<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

/**
 * Simple class to expand glob mappings to simple file mappings
 *
 * Class GlobExpander
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
final class GlobExpander
{

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $destination;
    /**
     * @var array
     */
    private $mappings;

    /**
     * @param string $source
     * @param string $destination
     * @param array $mappings
     */
    public function __construct($source, $destination, array $mappings)
    {
        //enforce type safety - each record should be an array
        array_map(function (array $map) {
        }, $mappings);


        $this->source = $source;
        $this->destination = $destination;
        $this->mappings = $mappings;
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    public function expand()
    {
        $mappings = array();
        foreach ($this->mappings as $mapping) {
            $relativeSource         = ltrim($mapping[0], '\\/');
            $relativeDestination    = trim($mapping[1], '\\/');
            $absoluteSource         = sprintf('%s/%s', $this->source, $relativeSource);

            if (file_exists($absoluteSource)) {
                //file is a file, we don't care about this
                $mappings[] = $mapping;
                continue;
            }

            //not a file, is it a glob?
            $iterator = new \GlobIterator($absoluteSource, \FilesystemIterator::KEY_AS_FILENAME);

            if (!$iterator->count()) {
                //maybe this error is wrong, as it could be a valid glob, just there were no results.
                throw new \ErrorException(
                    sprintf("Source %s does not exist and is not a valid glob expression", $absoluteSource)
                );
            }

            //add each glob as a separate mapping
            foreach ($iterator as $file) {
                $mappings[] = $this->processMapping($file, $relativeDestination);
            }
        }

        return $mappings;
    }

    /**
     * @param \SplFileInfo $globMatch
     * @param string $relativeDestination
     * @return array
     */
    protected function processMapping(\SplFileInfo $globMatch, $relativeDestination)
    {
        $absolutePath = $globMatch->getPathname();

        //get the relative path to this file/dir - strip of the source path
        //+1 to strip leading slash
        $source = substr($absolutePath, strlen($this->source) + 1);

        if ($globMatch->isDir()) {
            $destination = ltrim(sprintf('%s/%s', $relativeDestination, $source), '\\/');
        } else {
            $destination = ltrim(sprintf('%s/%s', $relativeDestination, $globMatch->getFilename()), '\\/');
        }

        return array($source, $destination);
    }
}
