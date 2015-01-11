<?php

namespace MagentoHackathon\Composer\Magento\InstallStrategy\Exception;

use RuntimeException;

/**
 * Class TargetExistsException
 * @package MagentoHackathon\Composer\Magento\InstallStrategy\Exception
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class TargetExistsException extends RuntimeException
{
    /**
     * @var string
     */
    protected $targetFilePath;

    /**
     * @param string $target
     */
    public function __construct($target)
    {
        $this->targetFilePath = $target;
        $message = sprintf('Target %s already exists (set extra.magento-force to override)', $target);
        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getTargetFilePath()
    {
        return $this->targetFilePath;
    }
}
