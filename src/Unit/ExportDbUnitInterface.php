<?php

namespace Maketok\DataMigration\Unit;

interface ExportDbUnitInterface extends UnitInterface
{
    /**
     * @return array
     */
    public function getReverseMoveOrder();

    /**
     * @param array $reverseMoveOrder
     */
    public function setReverseMoveOrder(array $reverseMoveOrder);

    /**
     * @return array
     */
    public function getReverseMoveDirection();

    /**
     * @param string $direction
     */
    public function setReverseMoveDirection($direction);

    /**
     * @return array
     */
    public function getReverseMoveConditions();

    /**
     * @param array $conditions
     */
    public function setReverseMoveConditions($conditions);
}
