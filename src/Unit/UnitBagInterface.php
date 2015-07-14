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
}
