<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\ExportDbUnitInterface;

abstract class ExportDbUnit extends ImportDbUnit implements ExportDbUnitInterface
{
    /**
     * Order by columns for reverse move
     * @var array
     */
    protected $reverseMoveOrder = [];
    /**
     * Direction for reverse move
     * @var string
     */
    protected $reverseDirection = 'asc';
    /**
     * Conditions for reverse move
     * @var array
     */
    protected $reverseConditions = [];

    /**
     * {@inheritdoc}
     */
    public function getReverseMoveOrder()
    {
        return $this->reverseMoveOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function setReverseMoveOrder(array $reverseMoveOrder)
    {
        $this->reverseMoveOrder = $reverseMoveOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getReverseMoveDirection()
    {
        return $this->reverseDirection;
    }

    /**
     * {@inheritdoc}
     */
    public function setReverseMoveDirection($direction)
    {
        $this->reverseDirection = $direction;
    }

    /**
     * {@inheritdoc}
     */
    public function getReverseMoveConditions()
    {
        return $this->reverseConditions;
    }

    /**
     * {@inheritdoc}
     */
    public function setReverseMoveConditions($conditions)
    {
        $this->reverseConditions = $conditions;
    }
}
