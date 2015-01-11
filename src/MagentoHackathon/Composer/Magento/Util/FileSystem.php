<?php

namespace MagentoHackathon\Composer\Magento\Util;

use Composer\Util\Filesystem as ComposerFs;

/**
 * Class FileSystem
 * @package MagentoHackathon\Composer\Magento\Util\Filesystem
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class FileSystem extends ComposerFs
{
    /**
     * Returns the relative path from $from to $to
     *
     * This is utility method for symlink creation.
     * Orig Source: http://stackoverflow.com/a/2638272/485589
     *
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public function getRelativePath($from, $to)
    {
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);

        $from = str_replace(array('/./', '//'), '/', $from);
        $to = str_replace(array('/./', '//'), '/', $to);

        $from = explode('/', $from);
        $to = explode('/', $to);

        $relPath = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }

    /**
     * @param string $absoluteSource
     * @param string $relativeDir
     *
     * @return string
     */
    public function makePathRelative($absoluteSource, $relativeDir)
    {
        return substr($absoluteSource, strlen($relativeDir) + 1);
    }

    /**
     * Check if the basename of source and directory match
     *
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    public function sourceAndDestinationBaseMatch($source, $destination)
    {
        $sourceBase         = basename($source);
        $destinationBase    = basename($destination);

        return $sourceBase === $destinationBase;
    }

    /**
     * If destination to correct location
     * return true
     *
     * @param string $destination
     * @param string $source
     *
     * @return bool
     */
    public function symLinkPointsToCorrectLocation($destination, $source)
    {
        $destinationAbsolutePath = realpath(readlink($destination));
        $sourceAbsolutePath      = realpath($source);

        return $destinationAbsolutePath === $sourceAbsolutePath;
    }

    /**
     * Create a symlink fixes various quirks on different OS's
     *
     * @param string $source
     * @param string $destination
     *
     * @throws \ErrorException
     */
    public function createSymlink($source, $destination)
    {
        $relativeSourcePath = $this->getRelativePath($destination, $source);
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $relativeSourcePath = str_replace('/', '\\', $relativeSourcePath);
            $param = is_dir($source) ? '/D' : '';
            exec(sprintf('mklink %s %s %s', $param, $destination, $relativeSourcePath), $output, $return);
        } else {
            $result = symlink($relativeSourcePath, $destination);

            if (false === $result) {
                throw new \ErrorException(sprintf('An error occurred while creating symlink: %s', $relativeSourcePath));
            }
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function endsWithDirectorySeparator($path)
    {
        return in_array(substr($path, -1), array('/', '\\'));
    }
}
