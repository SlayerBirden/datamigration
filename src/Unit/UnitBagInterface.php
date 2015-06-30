<?php

namespace Maketok\DataMigration\Unit;

interface UnitBagInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param UnitInterface $worker
     */
    public function add(UnitInterface $worker);
}
