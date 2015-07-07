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
    protected $reverseMoveDirections;
    /**
     * Conditions for reverse move
     * @var array
     */
    protected $reverseMoveConditions;

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
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReverseMoveDirections()
    {
        return $this->reverseMoveDirections;
    }

    /**
     * {@inheritdoc}
     */
    public function setReverseMoveDirections(array $reverseMoveDirections)
    {
        $this->reverseMoveDirections = $reverseMoveDirections;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReverseMoveConditions()
    {
        return $this->reverseMoveConditions;
    }

    /**
     * {@inheritdoc}
     */
    public function setReverseMoveConditions($reverseMoveConditions)
    {
        $this->reverseMoveConditions = $reverseMoveConditions;
        return $this;
    }
}
