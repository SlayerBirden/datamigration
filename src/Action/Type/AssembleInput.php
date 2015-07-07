<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
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
     * @var array
     */
    private $processed = [];
    /**
     * @var array
     */
    private $buffer = [];
    /**
     * @var array
     */
    private $connectBuffer = [];
    /**
     * @var array
     */
    private $lastAdded = [];

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param LanguageInterface $language
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $resourceHelper
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        InputResourceInterface $input,
        MapInterface $map,
        ResourceHelperInterface $resourceHelper
    ) {
        parent::__construct($bag, $config, $language);
        $this->input = $input;
        $this->map = $map;
        $this->resourceHelper = $resourceHelper;
    }

    /**
     * {@inheritdoc}
     * Reversed process to create input resource
     * @throws \LogicException, WrongContextException
     */
    public function process()
    {
        $this->start();
        while (true) {
            $this->processUnitRowData();
            if ($this->isEmptyData($this->processed)) {
                break 1; // exit point
            }
            $this->addData();
        }
        $this->close();
    }

    /**
     * @return array
     */
    private function processUnitRowData()
    {
        foreach ($this->bag as $unit) {
            $code = $unit->getCode();
            if (isset($this->buffer[$code])) {
                $this->processed[$code] = $tmpRow = $this->buffer[$code];
            } elseif (($tmpRow = $this->readRow($unit)) !== false) {
                $this->processed[$code] = $tmpRow;
            } else {
                throw new \LogicException(
                    sprintf("Do not know how to process the unit %s.", $unit->getCode())
                );
            }
            $this->connectBuffer[$code] = array_map(function ($var) use ($tmpRow) {
                if (!isset($tmpRow[$var])) {
                    throw new \LogicException("Wrong reversed connection key given.");
                } else {
                    return $tmpRow[$var];
                }
            }, $unit->getReversedConnection());
        }
        try {
            $this->assemble($this->connectBuffer);
        } catch (ConflictException $e) {
            $conflicted = $e->getUnitsInConflict();
            // assume 1st unit is major entity (which goes on multiple rows)
            $code = array_shift($conflicted);
            $this->buffer[$code] = $this->processed[$code];
            $this->processed[$code] = $this->lastAdded[$code];
        }
    }

    /**
     * procedure of adding data to input resource
     */
    private function addData()
    {
        $toAdd = [];
        $tmpRow = $this->assemble($this->processed, true);
        $this->map->feed($tmpRow);
        foreach ($this->bag as $unit) {
            if (!isset($this->processed[$unit->getCode()])) {
                continue;
            }
            $unitData = $this->processed[$unit->getCode()];
            $toAdd[$unit->getCode()] = array_map(function ($var) use ($unitData) {
                return $this->language->evaluate($var, [
                    'map' => $this->map,
                ]);
            }, $unit->getReversedMapping());
            $this->lastAdded[$unit->getCode()] = $this->processed[$unit->getCode()];
        }
        $row = $this->assemble($toAdd);
        $this->input->add($row);
    }

    /**
     * @param AbstractUnit $unit
     * @return array|bool
     * @throws WrongContextException
     */
    private function readRow(AbstractUnit $unit)
    {
        $row = $unit->getFilesystem()->readRow();
        if (is_array($row)) {
            return array_combine(array_keys($unit->getMapping()), $row);
        }
        return false;
    }

    /**
     * recursive array_filter
     * @param array $data
     * @return bool
     */
    public function isEmptyData(array $data)
    {
        $filteredData = array_filter($data, function ($var) {
            if (is_array($var)) {
                return !$this->isEmptyData($var);
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
                    $unit->getCode()
                ));
            }
            $unit->getFilesystem()->open($unit->getTmpFileName(), 'r');
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
    }

    /**
     * @param array $data
     * @param bool $force
     * @return array
     * @throws ConflictException
     */
    public function assemble(array $data, $force = false)
    {
        $row = [];
        $meta = [];
        foreach ($data as $unitCode => $mapping) {
            foreach ($mapping as $k => $v) {
                if (isset($row[$k]) && $row[$k] != $v && !$force) {
                    $meta[$k][] = $unitCode;
                    throw new ConflictException(
                        sprintf("Conflict with data %s, %s", json_encode($data), $v),
                        array_unique($meta[$k]),
                        $k
                    );
                } elseif (isset($row[$k]) && $row[$k] == $v) {
                    $meta[$k][] = $unitCode;
                } elseif (!isset($row[$k])) {
                    $row[$k] = $v;
                    if (!isset($meta[$k])) {
                        $meta[$k] = [];
                    }
                    $meta[$k][] = $unitCode;
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
