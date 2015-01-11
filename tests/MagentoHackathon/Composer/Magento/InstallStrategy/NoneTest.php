<?php
namespace MagentoHackathon\Composer\Magento\InstallStrategy;

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
        $this->assertSame(array(), $none->resolve('some/source', 'some/destination'));
    }

    public function testCreateDoesNothing()
    {
        $none = new None;
        $this->assertEquals(array(), $none->create('some/source', 'some/destination', false));
    }
}
