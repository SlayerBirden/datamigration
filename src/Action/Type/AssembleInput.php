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
     * @var ResourceInterface[]
     */
    private $handlers = [];
    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $resourceHelper
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        InputResourceInterface $input,
        MapInterface $map,
        ResourceHelperInterface $resourceHelper
    ) {
        parent::__construct($bag, $config);
        $this->input = $input;
        $this->map = $map;
        $this->resourceHelper = $resourceHelper;
    }

    /**
     * {@inheritdoc}
     * Reversed process to create input resource
     * THE ORDER OF UNITS MATTERS!
     * @throws \LogicException, WrongContextException
     */
    public function process()
    {
        $this->start();
        while (true) {
            $data = $this->getUnitRowData();
            if ($this->isEmptyData($data)) {
                break 1; // exit point
            }
            $this->addData($data);
        }
        $this->close();
    }

    /**
     * procedure of adding data to input resource
     * @param array $data
     */
    private function addData(array $data)
    {
        while (true) {
            try {
                $row = $this->assemble($data);
                $this->input->add($row);
                break 1;
            } catch (ConflictException $e) {
                $conflicted = $e->getUnitsInConflict();
                $unitToBuffer = array_pop($conflicted);
                if (isset($this->buffer[$unitToBuffer])) {
                    throw new \LogicException("Unhandled logic issue", 0, $e);
                }
                $this->buffer[$unitToBuffer] = $data[$unitToBuffer];
                foreach ($data[$unitToBuffer] as $k => $v) {
                    if ($k != $e->getConflictedKey()) {
                        $data[$unitToBuffer][$k] = null;
                    } else {
                        unset($data[$unitToBuffer][$k]);
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getUnitRowData()
    {
        $data = [];
        foreach ($this->bag as $unit) {
            if (isset($this->buffer[$unit->getTable()])) {
                $data[$unit->getTable()] = $this->buffer[$unit->getTable()];
                unset($this->buffer[$unit->getTable()]);
            } else {
                $handler = $this->handlers[$unit->getTable()];
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
                        $this->headers[$unit->getTable()] = array_keys($unitData);
                    }
                } elseif (isset($this->headers[$unit->getTable()])) {
                    $data[$unit->getTable()] = array_combine(
                        $this->headers[$unit->getTable()],
                        array_map(function () {
                            return null;
                        }, $this->headers[$unit->getTable()])
                    );
                } else {
                    // this unit doesn't have any data?!?
                    // in that case we don't have to use it
                }
            }
        }
        return $data;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function isEmptyData(array $data)
    {
        $filteredData = array_filter($data, function ($var) {
            if (is_array($var)) {
                $var = array_filter($var);
            }
            return !empty($var);
        });
        return empty($filteredData);
    }

    /**
     * open handlers
     * @throws WrongContextException
     */
    private function start()
    {
        foreach ($this->bag as $unit) {
            if ($unit->getTmpFileName() === null) {
                throw new WrongContextException(sprintf(
                    "Action can not be used for current unit %s. Tmp file is missing.",
                    $unit->getTable()
                ));
            }
            $handler = clone $this->filesystem;
            $handler->open($unit->getTmpFileName(), 'r');
            $this->handlers[$unit->getTable()] = $handler;
        }
    }

    /**
     * close all handlers
     */
    private function close()
    {
        foreach ($this->bag as $unit) {
            $handler = $this->handlers[$unit->getTable()];
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
