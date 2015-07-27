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
     * Units can be related in a tree-like structure:
     *
     *         1 - 2
     *        / \   \
     *       3  4    5 - 6
     * this should compile the structure to assign proper level and parentage to units
     * @return mixed
     */
    public function compileTree();

    /**
     * check if current unit is on the lowest level
     * @param string $code
     * @return bool
     */
    public function isLowest($code);

    /**
     * get bag's lowest level
     * @return int
     */
    public function getLowestLevel();

    /**
     * @param string $code
     * @return int
     */
    public function getUnitLevel($code);

    /**
     * get unit's children
     * @param string $code
     * @return UnitInterface[]
     */
    public function getChildren($code);

    /**
     * @param int $level
     * @return string[]
     */
    public function getUnitsFromLevel($level);

    /**
     * Return sets of units that relate to each other somehow (either parent-child or siblings)
     * Lowest levels first
     * @return array
     */
    public function getRelations();
}
