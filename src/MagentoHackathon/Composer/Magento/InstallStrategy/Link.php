<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

/**
 * Hardlink deploy strategy
 */
class Link extends DeploystrategyAbstract
{
    /**
     * Creates a hardlink with lots of error-checking
     *
     * @param string $source
     * @param string $destination
     *
     * @return bool
     * @throws \ErrorException
     * @internal param string $dest
     */
    public function createDelegate($source, $destination)
    {
        $source         = sprintf('%s/%s', $this->getSourceDir(), $this->removeTrailingSlash($source));
        $destination    = sprintf('%s/%s', $this->getDestDir(), $this->removeTrailingSlash($destination));

        if (is_dir($destination) && !$this->filesystem->sourceAndDestinationBaseMatch($source, $destination)) {
            // If the destination exists and is a directory
            // and basename of source and destination are not equal that means we want to copy
            // source into destination, not to destination
            // eg. src: code/Some_Module.xml dest: app/etc/modules
            // would result in Some_Module.xml being placed inside: app/etc/modules
            // - so: app/etc/modules/Some_Module.xml
            //
            $destination = sprintf('%s/%s', $destination, basename($source));
        }

        return $this->link($source, $destination);
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return array Array of all the files created
     * @throws \ErrorException
     */
    protected function link($source, $destination)
    {
        //Create all directories up to one below the target if they don't exist
        $this->filesystem->ensureDirectoryExists(dirname($destination));

        if (is_dir($source)) {
            return $this->linkDirectoryToDirectory($source, $destination);
        }

        // If file exists and force is not specified, throw exception unless FORCE is set
        if (file_exists($destination)) {
            if (!$this->isForced()) {
                throw new \ErrorException(
                    sprintf('Target %s already exists (set extra.magento-force to override)', $destination)
                );
            }
            unlink($destination);
        }

        $this->linkFileToFile($source, $destination);
        return array($destination);
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return array Array of all files created
     * @throws \ErrorException
     */
    protected function linkDirectoryToDirectory($source, $destination)
    {
        // Link dir to dir
        // First create destination folder if it doesn't exist
        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $createdFiles = array();
        foreach ($iterator as $item) {
            $absoluteDestination = sprintf('%s/%s', $destination, $iterator->getSubPathName());
            if ($item->isDir()) {
                if (!file_exists($absoluteDestination)) {
                    mkdir($absoluteDestination, 0777, true);
                }
            } else {
                $createdFiles[] = $this->linkFileToFile($item, $absoluteDestination);
            }
            if (!is_readable($absoluteDestination)) {
                throw new \ErrorException(sprintf('Could not create %s', $absoluteDestination));
            }
        }
        return $createdFiles;
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    protected function linkFileToFile($source, $destination)
    {
        return link($source, $destination);
    }
}
