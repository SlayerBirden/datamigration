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

    /**
     * {@inheritdoc}
     */
    public function isLeaf($code)
    {
        $checked = $this->getUnitByCode($code);
        if (!$checked->getParent()) {
            return false;
        }
        foreach ($this->units as $unit) {
            if ($unit->getCode() == $code) {
                continue;
            }
            if (($parent = $unit->getParent()) && $parent->getCode() == $code) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLeaf()
    {
        $res = false;
        foreach ($this->units as $unit) {
            if ($this->isLeaf($unit->getCode())) {
                $res = true;
                break 1;
            }
        }
        return $res;
    }
}
