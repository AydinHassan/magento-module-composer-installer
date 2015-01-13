<?php

namespace MagentoHackathon\Composer\Magento\Map;

use JsonSchema\Constraints\Collection;
use PHPUnit_Framework_TestCase;

/**
 * Class MapCollectionTest
 * @package MagentoHackathon\Composer\Magento\Map
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class MapCollectionTest extends PHPUnit_Framework_TestCase
{
    public function testCollectionThrowsExceptionIfNonMapPassedIn()
    {
        $this->setExpectedException('InvalidArgumentException', 'Input must be an array of "Map"');
        new MapCollection(array(new \stdClass));
    }

    public function testGetIterator()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');
        $map4 = new Map('source3', 'destination3', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3, $map4);

        $collection = new MapCollection($items);
        $this->assertSame($items, iterator_to_array($collection));
    }

    public function testCount()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');
        $map4 = new Map('source3', 'destination3', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3, $map4);

        $collection = new MapCollection($items);
        $this->assertCount(4, $collection);
    }

    public function testRemoveMap()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');
        $map4 = new Map('source3', 'destination3', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3, $map4);

        $collection = new MapCollection($items);
        $this->assertCount(4, $collection);

        $newCollection = $collection->remove($map2);
        $this->assertCount(4, $collection);
        $this->assertCount(3, $newCollection);

        $updatedItems = array($map1, $map3, $map4);
        $this->assertSame($updatedItems, iterator_to_array($newCollection));
    }

    public function testReplaceMap()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3);

        $collection = new MapCollection($items);
        $this->assertCount(3, $collection);

        $newMap1 = new Map('new-source', 'new-destination', '/tmp/', '/tmp/');
        $newMap2 = new Map('new-source1', 'new-destination1', '/tmp/', '/tmp/');

        $newCollection = $collection->replace($map2, array($newMap1, $newMap2));

        $expectedItems = array($map1, $newMap1, $newMap2, $map3);

        $this->assertCount(4, $newCollection);
        $this->assertCount(3, $collection);
        $this->assertSame($expectedItems, iterator_to_array($newCollection));
    }

    public function testReplaceThrowsExceptionIfReplacementArrayContainsElementWhichIsNotAMap()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3);

        $collection = new MapCollection($items);
        $this->assertCount(3, $collection);

        $this->setExpectedException('InvalidArgumentException', 'Input must be an array of "Map"');
        $collection->replace($map2, array(new \stdClass));
    }

    public function testReplaceThrowsExceptionIfMapDoesNotExistInCollection()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3);

        $collection = new MapCollection($items);

        $this->setExpectedException('InvalidArgumentException', 'Map does not belong to this collection');
        $collection->replace(new Map('nope', 'nope', 'nope', 'nope'), array());
    }

    public function testAllReturnsAllItems()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3);

        $collection = new MapCollection($items);
        $this->assertSame($items, $collection->all());
    }

    public function testFilterThrowsExceptionIfArgumentNotCallable()
    {
        $collection = new MapCollection(array());
        $this->setExpectedException('InvalidArgumentException', 'Expected callable, got "stdClass"');
        $collection->filter(new \stdClass);
    }

    public function testFilter()
    {
        $map1 = new Map('source', 'destination', '/tmp/', '/tmp/');
        $map2 = new Map('source1', 'destination1', '/tmp/', '/tmp/');
        $map3 = new Map('source2', 'destination2', '/tmp/', '/tmp/');

        $items = array($map1, $map2, $map3);

        $collection = new MapCollection($items);

        $filtered = $collection->filter(function (Map $map) {
            return $map->getSource() === 'source';
        });


        $this->assertSame(array($map1), $filtered->all());
    }
}
