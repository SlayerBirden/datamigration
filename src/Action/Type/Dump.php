<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Unit\ExportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Dump data from tmp table to tmp file
 */
class Dump extends AbstractDbAction implements ActionInterface
{
    /**
     * @var UnitBagInterface|ExportFileUnitInterface[]
     */
    protected $bag;
    /**
     * @var ResultInterface
     */
    protected $result;


    /**
     * {@inheritdoc}
     * @throws WrongContextException
     */
    public function process(ResultInterface $result)
    {
        $offset = 0;
        $limit = $this->config->offsetGet('dump_limit');
        $this->result = $result;
        try {
            $this->start();
            foreach ($this->bag as $unit) {
                while (($data = $this->resource->dumpData($unit->getTmpTable(),
                        array_keys($unit->getMapping()),
                        $limit,
                        $offset)) !== false) {
                    $offset += $limit;
                    foreach ($data as $row) {
                        $unit->getFilesystem()->writeRow($row);
                        $result->incrementActionProcessed($this->getCode());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->close();
            throw $e;
        }
        $this->close();
    }

    /**
     * open handlers
     * @throws WrongContextException
     */
    private function start()
    {
        $this->result->setActionStartTime($this->getCode(), new \DateTime());
        foreach ($this->bag as $unit) {
            if ($unit->getTmpTable() === null) {
                throw new WrongContextException(sprintf(
                    "Action can not be used for current unit %s. Tmp table is missing.",
                    $unit->getCode()
                ));
            }
            $unit->setTmpFileName($this->getTmpFileName($unit));
            $unit->getFilesystem()->open($unit->getTmpFileName(), 'w');
        }
    }

    /**
     * close all handlers
     */
    private function close()
    {
        foreach ($this->bag as $unit) {
            $unit->getFilesystem()->close();
        }
        $this->result->setActionEndTime($this->getCode(), new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'dump';
    }
}
