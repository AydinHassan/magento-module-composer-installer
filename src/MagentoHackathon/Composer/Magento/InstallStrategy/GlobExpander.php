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
            $relativeDestination    = ltrim($mapping[1], '\\/');

            $absoluteSource         = sprintf('%s/%s', $this->source, $relativeSource);
            $absoluteDestination    = sprintf('%s/%s', $this->destination, $relativeDestination);

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
                $absolutePath = $file->getPathname();
                $relativePath = substr($absolutePath, strlen($this->source) + 1); // +1 to strip leading slash
                $mappings[] = array($relativePath, $relativeDestination);
            }
        }

        return $mappings;
    }
}
