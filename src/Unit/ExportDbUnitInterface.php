<?php

namespace Maketok\DataMigration\Unit;

interface ExportDbUnitInterface extends ImportDbUnitInterface
{
    /**
     * @return array
     */
    public function getReverseMoveOrder();

    /**
     * @param array $reverseMoveOrder
     * @return $this
     */
    public function setReverseMoveOrder(array $reverseMoveOrder);

    /**
     * @return array
     */
    public function getReverseMoveDirections();

    /**
     * @param array $reverseMoveDirections
     * @return $this
     */
    public function setReverseMoveDirections(array $reverseMoveDirections);

    /**
     * @return array
     */
    public function getReverseMoveConditions();

    /**
     * @param array $reverseMoveConditions
     * @return $this
     */
    public function setReverseMoveConditions($reverseMoveConditions);
}
