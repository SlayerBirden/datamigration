<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\Action\Exception\FlowRegulationException;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Unit\ExportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Create Base Input stream from separate units
 */
class AssembleInput extends AbstractAction implements ActionInterface
{
    use ArrayUtilsTrait;

    const FLOW_CONTINUE = 100;
    const FLOW_ABORT = 200;

    /**
     * @var UnitBagInterface|ExportFileUnitInterface[]
     */
    protected $bag;
    /**
     * @var InputResourceInterface
     */
    private $input;
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * Current row being processed
     * @var array
     */
    private $processed = [];
    /**
     * Buffered row
     * @var array
     */
    private $buffer = [];
    /**
     * Entries that specify connection between units
     * @var array
     */
    private $connectBuffer = [];
    /**
     * Last added rows buffer
     * @var array
     */
    private $lastAdded = [];
    /**
     * Units that are empty
     * @var string[]
     */
    private $finished = [];
    /**
     * @var LanguageInterface
     */
    protected $language;
    /**
     * @var ResultInterface
     */
    protected $result;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param LanguageInterface $language
     * @param InputResourceInterface $input
     * @param MapInterface $map
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        InputResourceInterface $input,
        MapInterface $map
    ) {
        parent::__construct($bag, $config);
        $this->input = $input;
        $this->map = $map;
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     * Reversed process to create input resource
     * Order of the units matters!
     * @throws \LogicException
     * @throws WrongContextException
     */
    public function process(ResultInterface $result)
    {
        $this->result = $result;
        $this->start();
        while (true) {
            try {
                $this->processUnitRowData();
                $this->addData();
                $result->incrementActionProcessed($this->getCode());
            } catch (FlowRegulationException $e) {
                if ($e->getCode() === self::FLOW_ABORT) {
                    break 1; // exit point
                }
            } catch (\Exception $e) {
                $this->close();
                throw $e;
            }
            $this->clear();
        }
        $this->close();
    }

    /**
     * clear variable context
     */
    private function clear()
    {
        $this->connectBuffer = [];
        $this->processed = [];
    }

    /**
     * @return array
     */
    private function processUnitRowData()
    {
        foreach ($this->bag as $unit) {
            $code = $unit->getCode();
            if (in_array($code, $this->finished)) {
                continue;
            } elseif (isset($this->buffer[$code])) {
                $this->processed[$code] = $tmpRow = $this->buffer[$code];
            } elseif (($tmpRow = $this->readRow($unit)) !== false) {
                $this->processed[$code] = $tmpRow;
            } else {
                continue;
            }
            $this->connectBuffer[$code] = array_map(function ($var) use ($tmpRow) {
                if (!isset($tmpRow[$var])) {
                    throw new \LogicException("Wrong reversed connection key given.");
                } else {
                    return $tmpRow[$var];
                }
            }, $unit->getReversedConnection());
        }
        $this->analyzeRow();
        try {
            $this->assemble($this->connectBuffer);
        } catch (ConflictException $e) {
            $this->handleConflict($e->getUnitsInConflict());
        }
    }

    /**
     * analyze tmp rows before trying to assemble
     * @throws FlowRegulationException
     * @throws \LogicException
     */
    private function analyzeRow()
    {
        if ($this->isEmptyData($this->processed)) {
            throw new FlowRegulationException("", self::FLOW_ABORT);
        }
        // check if we have some missing units
        if (count($this->connectBuffer) < $this->bag->count()) {
            foreach ($this->bag as $unit) {
                if (!array_key_exists($unit->getCode(), $this->connectBuffer)) {
                    $this->finished[] = $unit->getCode();
                }
            }
            // checking if input has the correct # of mapped entries
            $intersected = array_intersect_key($this->connectBuffer, $this->buffer);
            foreach (array_keys($intersected) as $purgeKey) {
                unset($this->buffer[$purgeKey]);
                unset($this->connectBuffer[$purgeKey]);
            }
            if (!empty($this->connectBuffer)) {
                throw new \LogicException(
                    sprintf(
                        "Orphaned rows in some of the units %s",
                        json_encode(array_keys($this->connectBuffer))
                    )
                );
            }
            throw new FlowRegulationException("", self::FLOW_CONTINUE);
        }
    }

    /**
     * @param string[] $codes
     * @throws FlowRegulationException
     */
    private function handleConflict(array $codes)
    {
        if (empty($codes)) {
            throw new \LogicException("Can not resolve conflicted state of units.");
        }
        // assume 1st unit is major entity (which goes on multiple rows)
        $code = array_shift($codes);
        if (isset($this->buffer[$code])) {
            // time to purge buffer for conflicted code
            $this->buffer[$code] = null;
            $this->handleConflict($codes);
            throw new FlowRegulationException("", self::FLOW_CONTINUE);
        } elseif (isset($this->lastAdded[$code]) && isset($this->processed[$code])) {
            $this->buffer[$code] = $this->processed[$code];
            $this->processed[$code] = $this->lastAdded[$code];
        } else {
            // it seem we've got wrong connection from the start
            throw new \LogicException("Conflict is in the first row of given units. Will not process further.");
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
        foreach ($this->processed as $unitCode => $unitData) {
            /** @var ExportFileUnitInterface $unit */
            $unit = $this->bag->getUnitByCode($unitCode);
            $toAdd[$unitCode] = array_map(function ($var) use ($unit) {
                return $this->language->evaluate($var, [
                    'map' => $this->map,
                    'hashmaps' => $unit->getHashmaps(),
                ]);
            }, $unit->getReversedMapping());
            $this->lastAdded[$unitCode] = $unitData;
        }
        $this->input->add(
            $this->assemble($toAdd)
        );
    }

    /**
     * @param ExportFileUnitInterface $unit
     * @return array|bool
     * @throws WrongContextException
     */
    private function readRow(ExportFileUnitInterface $unit)
    {
        $row = $unit->getFilesystem()->readRow();
        if (is_array($row)) {
            return array_combine(array_keys($unit->getMapping()), $row);
        }
        return false;
    }

    /**
     * open handlers
     * @throws WrongContextException
     */
    private function start()
    {
        $this->result->setActionStartTime($this->getCode(), new \DateTime());
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
        $this->result->setActionEndTime($this->getCode(), new \DateTime());
    }

    /**
     * @param array $data
     * @param bool $force
     * @return array
     * @throws ConflictException
     */
    public function assemble(array $data, $force = false)
    {
        $byKeys = call_user_func_array('array_intersect_key', $data);
        $byKeysAndValues = call_user_func_array('array_intersect_assoc', $data);
        if ($byKeys != $byKeysAndValues && !$force) {
            $keys = array_keys(array_diff_assoc($byKeys, $byKeysAndValues));
            $key = array_shift($keys);
            $unitsInConflict = array_keys(array_filter($data, function ($var) use ($key) {
                return array_key_exists($key, $var);
            }));
            throw new ConflictException(
                sprintf("Conflict with data %s", json_encode($data)),
                0,
                null,
                $unitsInConflict,
                $key
            );
        }
        return call_user_func_array('array_replace', $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'assemble_input';
    }
}
