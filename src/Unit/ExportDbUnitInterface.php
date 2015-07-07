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
     * @param array $directions
     * @return $this
     */
    public function setReverseMoveDirections(array $directions);

    /**
     * @return array
     */
    public function getReverseMoveConditions();

    /**
     * @param array $conditions
     * @return $this
     */
    public function setReverseMoveConditions($conditions);
}
