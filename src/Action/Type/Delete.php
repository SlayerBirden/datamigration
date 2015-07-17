<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Unit\ImportDbUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Delete rows in table using tmp table as pk key mapper
 */
class Delete extends AbstractDbAction implements ActionInterface
{
    /**
     * @var UnitBagInterface|ImportDbUnitInterface[]
     */
    protected $bag;

    /**
     * {@inheritdoc}
     * @throws WrongContextException
     */
    public function process(ResultInterface $result)
    {
        $result->setActionStartTime($this->getCode(), new \DateTime());
        foreach ($this->bag as $unit) {
            if ($unit->getTmpTable() === null) {
                throw new WrongContextException(sprintf(
                    "Action can not be used for current unit %s. Tmp table is missing.",
                    $unit->getCode()
                ));
            }
            $args = [
                $unit->getTable(),
                $unit->getTmpTable()
            ];
            if ($unit->getPk() !== null) {
                $args[] = $unit->getPk();
            }
            $rowsDeleted = call_user_func_array([$this->resource, 'deleteUsingTempPK'], $args);
            $result->incrementActionProcessed($this->getCode(), $rowsDeleted);
        }
        $result->setActionEndTime($this->getCode(), new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'delete';
    }
}
