<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy\Exception;

use RuntimeException;

/**
 * Class SourceNotExistsException
 * @package MagentoHackathon\Composer\Magento\InstallStrategy\Exception
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SourceNotExistsException extends RuntimeException
{
    /**
     * @param string $source
     */
    public function __construct($source)
    {
        $message = sprintf('Source %s does not exist', $source);
        parent::__construct($message);
    }
}
