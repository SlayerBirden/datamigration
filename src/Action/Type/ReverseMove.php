<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Unit\ExportDbUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Move data from main table to tmp one
 */
class ReverseMove extends AbstractDbAction implements ActionInterface
{
    /**
     * @var UnitBagInterface|ExportDbUnitInterface[]
     */
    protected $bag;

    /**
     * {@inheritdoc}
     */
    public function process(ResultInterface $result)
    {
        $result->setActionStartTime($this->getCode(), new \DateTime());
        foreach ($this->bag as $unit) {
            $unit->setTmpTable($this->getTmpTableName($unit));
            $this->resource->createTmpTable(
                $unit->getTmpTable(),
                array_keys($unit->getMapping())
            );
            $moved = $this->resource->move(
                $unit->getTable(),
                $unit->getTmpTable(),
                null,
                // set export filters
                $unit->getReverseMoveConditions(),
                // need to be able to set order, as assemble depends on that
                $unit->getReverseMoveOrder(),
                $unit->getReverseMoveDirections()
            );
            $result->incrementActionProcessed($this->getCode(), $moved);
        }
        $result->setActionEndTime($this->getCode(), new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'reverse_move';
    }
}
