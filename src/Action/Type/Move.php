<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;

/**
 * Move data data from tmp table to main table
 */
class Move extends AbstractDbAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     * @throws WrongContextException
     */
    public function process()
    {
        foreach ($this->bag as $unit) {
            if ($unit->getTmpTable() === null) {
                throw new WrongContextException(sprintf(
                    "Action can not be used for current unit %s. Tmp table is missing.",
                    $unit->getTable()
                ));
            }
            $this->resource->move($unit->getTmpTable(), $unit->getTable());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'move';
    }
}
