<?php

namespace Maketok\DataMigration;

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
     * @param int $step
     * @return mixed
     */
    public function incr($key, $default, $step = 1);

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
}
