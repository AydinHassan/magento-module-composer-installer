<?php

namespace MagentoHackathon\Composer\Magento\Map;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Class MapCollection
 * @package MagentoHackathon\Composer\Magento\Map
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class MapCollection implements IteratorAggregate, Countable
{

    /**
     * @var Map[]
     */
    protected $maps;

    /**
     * @param array $maps
     */
    public function __construct(array $maps)
    {
        //enforce type safety
        array_map(function($map) {
            if (!$map instanceof Map) {
                throw new \InvalidArgumentException('Input must be an array of "Map"');
            }
        }, $maps);
        $this->maps = $maps;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->maps);
    }

    /**
     * @return Map[]
     */
    public function all()
    {
        return $this->maps;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->maps);
    }

    /**
     * @param Map $mapToRemove
     *
     * @return static
     */
    public function remove(Map $mapToRemove)
    {
        return new static(array_values(array_filter(
            $this->maps,
            function (Map $map) use ($mapToRemove) {
                return $mapToRemove !== $map;
            }
        )));
    }

    /**
     * @param Map   $mapToReplace
     * @param Map[] $replacementMaps
     *
     * @return static
     */
    public function replace(Map $mapToReplace, array $replacementMaps)
    {
        //enforce type safety
        array_map(function($map) {
            if (!$map instanceof Map) {
                throw new \InvalidArgumentException('Input must be an array of "Map"');
            }
        }, $replacementMaps);

        $key = array_search($mapToReplace, $this->maps);
        if (false === $key) {
            throw new \InvalidArgumentException('Map does not belong to this collection');
        }

        $maps = $this->maps;
        array_splice($maps, $key, 1, $replacementMaps);
        return new static($maps);
    }

    /**
     * @param $callback
     * @return static
     */
    public function filter($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(
                sprintf('Expected callable, got "%s"', is_object($callback) ? get_class($callback) : gettype($callback))
            );
        }

        return new static(array_filter($this->maps, $callback));
    }
}
