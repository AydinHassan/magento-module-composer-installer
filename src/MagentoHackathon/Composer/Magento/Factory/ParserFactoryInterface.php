<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Parser\ParserInterface;

/**
 * Interface ParserFactoryInterface
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
interface ParserFactoryInterface
{

    /**
     * @param PackageInterface $package
     * @param string $sourceDir
     * @return ParserInterface
     */
    public function make(PackageInterface $package, $sourceDir);
}
