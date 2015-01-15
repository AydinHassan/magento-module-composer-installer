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
     * @var string
     */
    protected $sourceFilePath;

    /**
     * @param string $source
     */
    public function __construct($source)
    {
        $this->sourceFilePath = $source;
        $message = sprintf('Source "%s" does not exist', $source);
        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getSourceFilePath()
    {
        return $this->sourceFilePath;
    }
}
