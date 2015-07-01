<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;

/**
 * Delete rows in table using tmp table as pk key mapper
 */
class Delete extends AbstractDbAction implements ActionInterface
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
            $args = [
                $unit->getTable(),
                $unit->getTmpTable()
            ];
            if ($unit->getPk() !== null) {
                $args[] = $unit->getPk();
            }
            call_user_func_array([$this->resource, 'deleteUsingTempPK'], $args);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'delete';
    }
}