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
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Unit\UnitInterface;
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
     * @var UnitBagInterface|ExportFileUnitInterface[]|ImportFileUnitInterface[]
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
     * read buffer
     * @var array
     */
    private $buffer = [];
    /**
     * write buffer
     * @var array
     */
    private $writeBuffer = [];
    /**
     * Entries that specify connection between units
     * @var array
     */
    private $connectBuffer = [];
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
                if ($this->bag->getLowestLevel() == 1) {
                    $this->dumpWriteBuffer();
                }
                $this->addData();
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
        $this->dumpWriteBuffer();
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
     * read data
     */
    private function processRead()
    {
        foreach ($this->bag as $unit) {
            $code = $unit->getCode();
            if (in_array($code, $this->finished)) {
                continue;
            } elseif (isset($this->buffer[$code])) {
                $this->processed[$code] = $tmpRow = $this->buffer[$code];
            } elseif (($tmpRow = $this->readRow($unit)) !== false) {
                $this->processed[$code] = $tmpRow;
                if (!$this->bag->isLowest($code)) {
                    $this->buffer[$code] = $this->processed[$code];
                }
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
    }

    /**
     * try to assemble
     * @throws ConflictException
     * @throws FlowRegulationException
     */
    private function processAssemble()
    {
        foreach ($this->bag->getRelations() as $rel) {
            $code = key($rel);
            $set = current($rel);
            $setCodes = array_map(function (UnitInterface $unit) {
                return $unit->getCode();
            }, $set);
            $codes = array_intersect_key($this->connectBuffer, array_flip($setCodes));
            switch ($code) {
                case 'pc': //parent-child
                    try {
                        $this->assemble($codes);
                    } catch (ConflictException $e) {
                        $this->handleConflict(array_keys($codes));
                    }
                    break;
                case 's'://siblings
                    $this->assemble($codes);
            }
        }
    }

    /**
     * @return array
     */
    private function processUnitRowData()
    {
        $this->processRead();
        $this->analyzeRow();
        $this->processAssemble();
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
            if (!$this->isEmptyData($this->connectBuffer)) {
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
     * @throws \LogicException
     */
    private function handleConflict(array $codes)
    {
        foreach ($codes as $code) {
            if ($this->cleanBuffer($code)) {
                continue;
            }
            $this->fillBuffer($code);
        }
        $this->dumpWriteBuffer();
        throw new FlowRegulationException("", self::FLOW_CONTINUE);
    }

    /**
     * dupm existing write buffer
     */
    private function dumpWriteBuffer()
    {
        if (!empty($this->writeBuffer)) {
            $this->input->add($this->writeBuffer);
            $this->result->incrementActionProcessed($this->getCode());
        }
        $this->writeBuffer = [];
    }

    /**
     * clean buffer for code
     * @param string $code
     * @return bool
     */
    protected function cleanBuffer($code)
    {
        $unit = $this->bag->getUnitByCode($code);
        $siblings = $unit->getSiblings();
        $cleaned = false;
        while (isset($this->buffer[$code])) {
            unset($this->buffer[$code]);
            $cleaned = true;
            $sibling = array_shift($siblings);
            if ($sibling) {
                $code = $sibling->getCode();
            }
        }
        return $cleaned;
    }

    /**
     * fill buffer for code
     * @param string $code
     * @return bool
     */
    public function fillBuffer($code)
    {
        $unit = $this->bag->getUnitByCode($code);
        $siblings = $unit->getSiblings();
        $filled = false;
        while (!isset($this->buffer[$code]) && isset($this->processed[$code])) {
            $this->buffer[$code] = $this->processed[$code];
            $filled = true;
            $sibling = array_shift($siblings);
            if ($sibling) {
                $code = $sibling->getCode();
            }
        }
        return $filled;
    }

    /**
     * procedure of adding data to input resource
     */
    private function addData()
    {
        $toAdd = [];
        $tmpRow = $this->assembleResolve($this->processed);
        $this->map->feed($tmpRow);
        foreach ($this->processed as $unitCode => $unitData) {
            /** @var ExportFileUnitInterface|ImportFileUnitInterface $unit */
            $unit = $this->bag->getUnitByCode($unitCode);
            $toAdd[$unitCode] = array_map(function ($var) use ($unit) {
                return $this->language->evaluate($var, [
                    'map' => $this->map,
                    'hashmaps' => $unit->getHashmaps(),
                ]);
            }, $unit->getReversedMapping());
        }
        foreach ($this->bag as $unit) {
            if ($this->bag->isLowest($unit->getCode())) {
                unset($this->buffer[$unit->getCode()]);
            }
        }
        $this->writeBuffer = $this->assembleHierarchy($toAdd);
    }

    /**
     * @param array $toAdd
     * @param int $level
     * @return array
     */
    private function assembleHierarchy(array $toAdd, $level = 1)
    {
        $topUnits = $this->bag->getUnitsFromLevel($level);
        $return = $this->assemble(array_intersect_key($toAdd, array_flip($topUnits)));
        if ($level == 1) {
            $return = array_replace_recursive($this->writeBuffer, $return);
        }
        foreach ($topUnits as $code) {
            $children = $this->bag->getChildren($code);
            foreach ($children as $child) {
                if (isset($return[$child->getCode()]) && is_array($return[$child->getCode()])) {
                    $return[$child->getCode()][] = $this->assembleHierarchy(
                        $toAdd,
                        $this->bag->getUnitLevel($child->getCode())
                    );
                } else {
                    $return[$child->getCode()] = [
                        $this->assembleHierarchy(
                            $toAdd,
                            $this->bag->getUnitLevel($child->getCode())
                        )
                    ];
                }
            }
        }
        return $return;
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @return array|bool
     * @throws WrongContextException
     */
    private function readRow(ImportFileUnitInterface $unit)
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
        $this->bag->compileTree();
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
        if (!$this->config['file_debug']) {
            foreach ($this->bag as $unit) {
                $unit->getFilesystem()->cleanUp($unit->getTmpFileName());
            }
        }
        $this->result->setActionEndTime($this->getCode(), new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'assemble_input';
    }
}
