<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\ExportDbUnitInterface;

class ExportDbUnit extends ImportDbUnit implements ExportDbUnitInterface
{
    /**
     * Order by columns for reverse move
     * @var array
     */
    protected $reverseMoveOrder;
    /**
     * Directions for reverse move
     * @var array
     */
    protected $reverseDirections;
    /**
     * Conditions for reverse move
     * @var array
     */
    protected $reverseConditions;

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
    public function getReverseMoveDirections()
    {
        return $this->reverseDirections;
    }

    /**
     * {@inheritdoc}
     */
    public function setReverseMoveDirections(array $directions)
    {
        $this->reverseDirections = $directions;
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
