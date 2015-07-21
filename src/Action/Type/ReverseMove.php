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
            $values = array_map(function () {
                return 'text';
            }, $unit->getMapping());
            $this->resource->createTmpTable(
                $unit->getTmpTable(),
                array_combine(array_keys($unit->getMapping()), $values)
            );
            $moved = $this->resource->move(
                $unit->getTable(),
                $unit->getTmpTable(),
                array_keys($unit->getMapping()),
                // set export filters
                $unit->getReverseMoveConditions(),
                // need to be able to set order, as assemble depends on that
                $unit->getReverseMoveOrder(),
                $unit->getReverseMoveDirection()
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
