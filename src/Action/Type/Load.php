<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Unit\ImportDbUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Load data from tmp files to tmp tables
 */
class Load extends AbstractDbAction implements ActionInterface
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
        foreach ($this->bag as $unit) {
            if ($unit->getTmpFileName() === null) {
                throw new WrongContextException(sprintf(
                    "Action can not be used for current unit %s. Tmp file is missing.",
                    $unit->getTable()
                ));
            }
            $unit->setTmpTable($this->getTmpTableName($unit));
            $this->resource->createTmpTable(
                $unit->getTmpTable(),
                array_keys($unit->getMapping())
            );
            $this->resource->loadData(
                $unit->getTmpTable(),
                $unit->getTmpFileName(),
                $this->config->offsetGet('local_infile')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'load';
    }
}
