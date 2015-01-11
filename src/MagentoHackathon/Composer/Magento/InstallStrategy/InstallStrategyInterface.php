<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\InstallStrategy\Exception\TargetExistsException;

/**
 * Interface InstallStrategyInterface
 * @package MagentoHackathon\Composer\Magento\InstallStrategyOld
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface InstallStrategyInterface
{

    /**
     * Resolve the mappings. Should return an array of mappings which
     * should be created. Eg in the case of copy, if source is a directory, then each file inside that directory
     * should be returned as a mapping.
     *
     * @param string $source
     * @param string $destination
     *
     * @return array
     * @throws TargetExistsException
     */
    public function resolve($source, $destination);

    /**
     * @param string    $source Absolute Path of source
     * @param string    $destination Absolute Path of destination
     * @param bool      $force Whether the creation should be forced (eg if it exists already)
     *
     * @return array Should return an array of files which were created
     *               Created directories should not be returned.
     */
    public function create($source, $destination, $force);
}
