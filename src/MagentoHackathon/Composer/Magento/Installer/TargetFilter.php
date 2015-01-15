<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\PackageInterface;

/**
 * Class TargetFilter
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class TargetFilter
{
    /**
     * @var array
     */
    protected $ignores;

    /**
     * @param array $ignores
     */
    public function __construct(array $ignores)
    {
        $this->ignores = $ignores;
    }

    /**
     * @param PackageInterface $package
     * @param string $target
     * @return bool
     */
    public function isTargetIgnored(PackageInterface $package, $target)
    {
        $moduleSpecificDeployIgnores = array();

        if (isset($this->ignores['*'])) {
            $moduleSpecificDeployIgnores = $this->ignores['*'];
        }

        if (isset($this->ignores[$package->getName()])) {
            $moduleSpecificDeployIgnores = array_merge(
                $moduleSpecificDeployIgnores,
                $this->ignores[$package->getName()]
            );
        }

        //prepend all ignores with '/' if they do not have it already
        $moduleSpecificDeployIgnores = array_map(
            function ($ignore) {
                return sprintf('/%s', ltrim($ignore, '\\/'));
            },
            $moduleSpecificDeployIgnores
        );

        $target = sprintf('/%s', ltrim($target, '\\/'));
        $target = str_replace('/./', '/', $target);
        $target = str_replace('//', '/', $target);

        return count(
            array_filter(
                $moduleSpecificDeployIgnores,
                function ($ignore) use ($target) {
                    return $target === $ignore;
                }
            )
        ) > 0;
    }
}
