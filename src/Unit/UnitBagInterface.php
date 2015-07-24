<?php

namespace Maketok\DataMigration\Unit;

interface UnitBagInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param UnitInterface $unit
     */
    public function add(UnitInterface $unit);

    /**
     * @param string $code
     * @return UnitInterface|bool (false)
     */
    public function getUnitByCode($code);

    /**
     * return if current unit is leaf
     * @param string $code
     * @return bool
     */
    public function isLeaf($code);

    /**
     * checks if bag contains a single leaf
     * @return bool
     */
    public function hasLeaf();
}
