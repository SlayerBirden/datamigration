<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;

interface MapInterface extends \ArrayAccess, \IteratorAggregate
{
    /**
     * Feed new row to map
     * @param array $row
     * @return self
     */
    public function feed(array $row);

    /**
     * Check is row is fresh (don't need to feed row multiple times)
     * @param array $row
     * @return bool
     */
    public function isFresh(array $row);

    /**
     * Clear internal data array
     * clean data
     */
    public function clear();

    /**
     * attempt to increment and get result value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function incr($key, $default);

    /**
     * get value by key
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Dump current image of an internal data array
     * @return array
     */
    public function dumpState();

    /**
     * Set current state (without feed's work)
     * @param array $state
     * @return mixed
     */
    public function setState(array $state);

    /**
     * Refresh all internal data, pointers, counters etc
     * @return mixed
     */
    public function refresh();

    /**
     * @param array $row
     * @param array $mapping
     * @param ResourceHelperInterface $helperResource
     * @return string[]
     */
    public function doMapping(array $row, array $mapping, ResourceHelperInterface $helperResource);

    /**
     * @return string[]
     */
    public function doReverse();
}
