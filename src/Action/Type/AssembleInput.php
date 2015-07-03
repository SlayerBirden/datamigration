<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

/**
 * Create Base Input stream from separate units
 */
class AssembleInput extends AbstractAction implements ActionInterface
{
    /**
     * @var InputResourceInterface
     */
    private $input;
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * @var ResourceHelperInterface
     */
    private $resourceHelper;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $resourceHelper
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem,
                                InputResourceInterface $input,
                                MapInterface $map,
                                ResourceHelperInterface $resourceHelper)
    {
        parent::__construct($bag, $config, $filesystem);
        $this->input = $input;
        $this->map = $map;
        $this->resourceHelper = $resourceHelper;
    }

    /**
     * {@inheritdoc}
     * Reversed process to create input resource
     * It's dependent on order of the data in Units
     * No mapping is done to map data in different positions
     *
     * @throws \LogicException, WrongContextException
     */
    public function process()
    {
        $handlers = [];
        // preparation
        foreach ($this->bag as $unit) {
            if ($unit->getTmpFileName() === null) {
                throw new WrongContextException(sprintf(
                    "Action can not be used for current unit %s. Tmp file is missing.",
                    $unit->getTable()
                ));
            }
            $handler = clone $this->filesystem;
            $handler->open($unit->getTmpFileName(), 'r');
            $handlers[$unit->getTable()] = $handler;
        }
        $buffer = [];
        while (true) {
            $data = [];
            foreach ($this->bag as $unit) {
                if (isset($buffer[$unit->getTable()])) {
                    $data[$unit->getTable()] = $buffer[$unit->getTable()];
                    unset($buffer[$unit->getTable()]);
                } else {
                    /** @var ResourceInterface $handler */
                    $handler = $handlers[$unit->getTable()];
                    $unitData = [];
                    if (($tmpReadRow = $handler->readRow()) !== false) {
                        $tmpRow = array_combine(array_keys($unit->getMapping()), $tmpReadRow);
                        foreach ($unit->getReversedMapping() as $k => $v) {
                            if (isset($tmpRow[$v])) {
                                if (is_callable($tmpRow[$v])) {
                                    // should return string
                                    $unitData[$k] = call_user_func($tmpRow[$v]);
                                } else {
                                    $unitData[$k] = $tmpRow[$v];
                                }
                            }
                        }
                        if (false !== $unitData) {
                            $data[$unit->getTable()] = $unitData;
                        }
                    }
                }
            }
            if (empty($data)) {
                break; // exit point
            }

            // try to assemble data
            while (true) {
                try {
                    $row = $this->assemble($data);
                    break;
                } catch (ConflictException $e) {
                    $conflicted = $e->getUnitsInConflict();
                    $unitToBuffer = array_pop($conflicted);
                    if (isset($buffer[$unitToBuffer])) {
                        throw new \LogicException("Unhandled logic issue", 0, $e);
                    }
                    $buffer[$unitToBuffer] = $data[$unitToBuffer];
                    foreach ($data[$unitToBuffer] as $k => $v) {
                        if ($k != $e->getConflictedKey()) {
                            $data[$unitToBuffer][$k] = null;
                        } else {
                            unset($data[$unitToBuffer][$k]);
                        }
                    }
                }
            }
            $this->input->add($row);
        }
        foreach ($this->bag as $unit) {
            /** @var ResourceInterface $handler */
            $handler = $handlers[$unit->getTable()];
            $handler->close();
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws ConflictException
     */
    public function assemble(array $data)
    {
        $row = [];
        $meta = [];
        foreach ($data as $unit => $mapping) {
            foreach ($mapping as $k => $v) {
                if (isset($row[$k]) && $row[$k] != $v) {
                    $meta[$k][] = $unit;
                    throw new ConflictException(
                        sprintf("Conflict with data %s, %s", json_encode($data), $v),
                        array_unique($meta[$k]),
                        $k
                    );
                } elseif (isset($row[$k]) && $row[$k] == $v) {
                    $meta[$k][] = $unit;
                } elseif (!isset($row[$k])) {
                    $row[$k] = $v;
                    if (!isset($meta[$k])) {
                        $meta[$k] = [];
                    }
                    $meta[$k][] = $unit;
                }
            }
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'assemble_input';
    }
}
