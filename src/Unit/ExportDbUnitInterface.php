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
     */
    public function setReverseMoveOrder(array $reverseMoveOrder);

    /**
     * @return array
     */
    public function getReverseMoveDirections();

    /**
     * @param array $directions
     */
    public function setReverseMoveDirections(array $directions);

    /**
     * @return array
     */
    public function getReverseMoveConditions();

    /**
     * @param array $conditions
     */
    public function setReverseMoveConditions($conditions);
}
