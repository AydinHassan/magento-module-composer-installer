<?php

namespace MagentoHackathon\Composer\Magento\Map;

use PHPUnit_Framework_TestCase;

/**
 * Class MapTest
 * @package MagentoHackathon\Composer\Magento\Map
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class MapTest extends PHPUnit_Framework_TestCase
{
    public function testGetRawValuesAreNotTrimmed()
    {
        $map = new Map('/source/', '/destination', '/tmp/', '/tmp/');

        $this->assertEquals('/source/', $map->getRawSource());
        $this->assertEquals('/destination', $map->getRawDestination());
    }

    public function testGetSourceAndDestinationReturnTrimmedValues()
    {
        $map = new Map('/source/', '/destination/', '/tmp/', '/tmp/');

        $this->assertEquals('source', $map->getSource());
        $this->assertEquals('destination', $map->getDestination());
    }

    public function testGetAbsolutePathsTrimValues()
    {
        $map = new Map('/source/', '/destination/', '/tmp/', '/tmp/');

        $this->assertEquals('/tmp/source', $map->getAbsoluteSource());
        $this->assertEquals('/tmp/destination', $map->getAbsoluteDestination());
    }
}
