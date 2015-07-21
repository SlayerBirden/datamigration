<?php

namespace Maketok\DataMigration\Unit;

class SimpleBag implements UnitBagInterface
{
    /**
     * @var UnitInterface[]
     */
    protected $units;

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->units));
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->units);
    }

    /**
     * {@inheritdoc}
     */
    public function add(UnitInterface $unit)
    {
        $this->units[$unit->getCode()] = $unit;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitByCode($code)
    {
        if (isset($this->units[$code])) {
            return $this->units[$code];
        }
        return false;
    }
}
