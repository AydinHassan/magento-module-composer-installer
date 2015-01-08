<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

/**
 * Symlink deploy strategy
 */
class Symlink extends DeploystrategyAbstract
{
    /**
     * Creates a symlink with lots of error-checking
     *
     * @param string $source
     * @param string $destination
     * @return array Array of all the files created
     * @throws \ErrorException
     */
    public function createDelegate($source, $destination)
    {
        $source         = sprintf('%s/%s', $this->getSourceDir(), $this->removeTrailingSlash($source));
        $destination    = sprintf('%s/%s', $this->getDestDir(), $this->removeTrailingSlash($destination));

//        if (!is_file($source) && !is_dir($source)) {
//            throw new \ErrorException(sprintf('Could not find path "%s"', $source));
//        }

        /*

        Assume app/etc exists, app/etc/a does not exist unless specified differently

        OK dir app/etc/a  --> link app/etc/a to dir
        OK dir app/etc/   --> link app/etc/dir to dir
        OK dir app/etc    --> link app/etc/dir to dir

        OK dir/* app/etc     --> for each dir/$file create a target link in app/etc
        OK dir/* app/etc/    --> for each dir/$file create a target link in app/etc
        OK dir/* app/etc/a   --> for each dir/$file create a target link in app/etc/a
        OK dir/* app/etc/a/  --> for each dir/$file create a target link in app/etc/a

        OK file app/etc    --> link app/etc/file to file
        OK file app/etc/   --> link app/etc/file to file
        OK file app/etc/a  --> link app/etc/a to file
        OK file app/etc/a  --> if app/etc/a is a file throw exception unless force is set, in that case rm and see above
        OK file app/etc/a/ --> link app/etc/a/file to file regardless if app/etc/a existst or not

        */

        // Handle source to dir linking,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        // Namespace/ModuleDir => Namespace/
        // Namespace/ModuleDir => Namespace/, but Namespace/ModuleDir may exist
        // Namespace/ModuleDir => Namespace/ModuleDir, but ModuleDir may exist

        if (is_dir($destination)) {
            $destination = sprintf('%s/%s', $destination, basename($source));
        }

        if (is_dir($destination) && $this->filesystem->sourceAndDestinationBaseMatch($source, $destination)) {
            if (!$this->isForced()) {
                throw new \ErrorException(
                    sprintf(
                        'Target %s already exists (set extra.magento-force to override)',
                        $this->filesystem->makePathRelative($destination, $this->getDestDir())
                    )
                );
            }
            $this->filesystem->remove($destination);
        }

        return $this->symlink($source, $destination);
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return array Array of all the files created
     * @throws \ErrorException
     */
    protected function symlink($source, $destination)
    {
        if (is_link($destination)) {
            if ($this->filesystem->symLinkPointsToCorrectLocation($destination, $source)) {
                return array();
            }
            unlink($destination);
        }

        $this->filesystem->ensureDirectoryExists(dirname($destination));

        // If file exists and force is not specified, throw exception unless FORCE is set
        // existing symlinks are already handled
        if (file_exists($destination)) {
            if (!$this->isForced()) {
                throw new \ErrorException(
                    sprintf(
                        'Target %s already exists (set extra.magento-force to override)',
                        $this->filesystem->makePathRelative($destination, $this->getSourceDir())
                    )
                );
            }
            unlink($destination);
        }

        $this->filesystem->createSymlink($source, $destination);
        return array($destination);
    }
}
