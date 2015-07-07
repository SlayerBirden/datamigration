<?php

namespace Maketok\DataMigration\Unit;

interface UnitBagInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param UnitInterface $worker
     */
    public function add(UnitInterface $worker);

    /**
     * @param string $code
     * @return UnitInterface|bool (false)
     */
    public function getUnitByCode($code);
}
