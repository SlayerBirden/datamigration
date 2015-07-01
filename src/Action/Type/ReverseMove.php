<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;

/**
 * Move data from main table to tmp one
 */
class ReverseMove extends AbstractDbAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        foreach ($this->bag as $unit) {
            $unit->setTmpTable($this->getTmpTableName($unit));
            $this->resource->move($unit->getTable(), $unit->getTmpTable());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'reverse_move';
    }
}
