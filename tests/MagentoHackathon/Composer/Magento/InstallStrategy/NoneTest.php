<?php
namespace MagentoHackathon\Composer\Magento\InstallStrategy;

use MagentoHackathon\Composer\Magento\Map\Map;
use PHPUnit_Framework_TestCase;

/**
 * Class NoneTest
 * @package MagentoHackathon\Composer\Magento\InstallStrategy
 */
class NoneTest extends PHPUnit_Framework_TestCase
{
    public function testResolveReturnsEmptyArray()
    {
        $none = new None;
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\InstallStrategy\InstallStrategyInterface', $none);
        $this->assertSame(array(), $none->resolve('some/source', 'some/destination', '/root', '/root'));
    }

    public function testCreateDoesNothing()
    {
        $none = new None;
        $map = new Map('some/source', 'some/destination', '/root', '/root');
        $this->assertNull($none->create($map, false));
    }
}
