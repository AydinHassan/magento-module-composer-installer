<?php

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\Package;
use PHPUnit_Framework_TestCase;

/**
 * Class TargetFilterTest
 * @package MagentoHackathon\Composer\Magento\Installer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class TargetFilterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var TargetFilter
     */
    protected $targetFilter;

    public function setUp()
    {
        $ignores = [
            '*' => [
                'global-ignore-me'
            ],
            'ignored/package' => [
                'ignore-me-1',
                'ignore-me-2'
            ]
        ];
        $this->targetFilter = new TargetFilter($ignores);
    }

    public function testGlobalIgnoreAppliesIfNoModuleSpecificIgnores()
    {
        $package = new Package('global/ignore', '1.0.0', 'global/ignore');
        $this->assertTrue($this->targetFilter->isTargetIgnored($package, 'global-ignore-me'));
        $this->assertFalse($this->targetFilter->isTargetIgnored($package, 'not-ignored-file'));
    }

    public function testPackageSpecificIgnore()
    {
        $package = new Package('ignored/package', '1.0.0', 'ignored/package');
        $this->assertTrue($this->targetFilter->isTargetIgnored($package, 'ignore-me-1'));
        $this->assertTrue($this->targetFilter->isTargetIgnored($package, 'ignore-me-2'));
        $this->assertFalse($this->targetFilter->isTargetIgnored($package, 'do-not-ignore-me'));
        $this->assertTrue($this->targetFilter->isTargetIgnored($package, 'global-ignore-me'));
    }
}
