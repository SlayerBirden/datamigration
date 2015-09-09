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
     * Last processed data for unit
     * @var array
     */
    private $lastProcessed = [];
    /**
     * read buffer
     * @var array
     */
    private $buffer = [];
    /**
     * persistent buffer serves as a deeper storage than orig buffer
     * it will serve value into orig buffer once it's missing there
     * @var array
     */
    private $persistentBuffer = [];
    /**
     * Storage that holds keys from pB that should be unset by the end of flow
     * @var array
     */
    private $toUnset = [];
    /**
     * Temporary buffer that's cleared right after serving data
     * @var array
     */
    private $tmpBuffer = [];
    /**
     * write buffer
     * @var array
     */
    private $writeBuffer = [];
    /**
     * small storage to hold info about the lasting buffer records
     * (if current processed was served from buffer)
     * @var array
     */
    private $lastingBuffer = [];
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
        $this->toUnset = [];
        $this->lastingBuffer = [];
    }

    /**
     * read data
     */
    private function processRead()
    {
        foreach ($this->bag as $unit) {
            $code = $unit->getCode();
            $this->prepareRead($code);
            if (isset($this->tmpBuffer[$code])) {
                $this->processed[$code] = $tmpRow = $this->tmpBuffer[$code];
                unset($this->tmpBuffer[$code]);
                $this->lastingBuffer[$code] = false;
                if (!$this->bag->isLowest($code)) {
                    $this->buffer[$code] = $this->processed[$code];
                }
            } elseif (in_array($code, $this->finished)) {
                continue;
            } elseif (isset($this->buffer[$code])) {
                $this->processed[$code] = $tmpRow = $this->buffer[$code];
                $this->lastingBuffer[$code] = true;
            } elseif (($tmpRow = $this->readRow($unit)) !== false) {
                $this->processed[$code] = $tmpRow;
                $this->lastingBuffer[$code] = false;
                if (!$this->bag->isLowest($code)) {
                    $this->buffer[$code] = $this->processed[$code];
                }
            } else {
                continue;
            }
            if (isset($this->processed[$code])) {
                $this->lastProcessed[$code] = $this->processed[$code];
            }
            // do not add all nulls to connectBuffer
            if ($this->isEmptyData($tmpRow)) {
                $this->connectBuffer[$code] = [];
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
     * @param string $code
     */
    private function prepareRead($code)
    {
        if (!isset($this->tmpBuffer[$code]) && isset($this->persistentBuffer[$code])) {
            $this->tmpBuffer[$code] = $this->persistentBuffer[$code];
            $this->toUnset[$code] = true;
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
                case 's': //siblings
                    try {
                        $this->assemble($codes);
                    } catch (ConflictException $e) {
                        $this->handleConflictedSibling(array_keys($codes));
                    }
                    break;
            }
        }
    }

    /**
     * @param array $codes
     * @throws FlowRegulationException
     */
    private function handleConflictedSibling(array $codes)
    {
        foreach ($codes as $code) {
            /** @var ExportFileUnitInterface $unit */
            $unit = $this->bag->getUnitByCode($code);
            if ($unit->isOptional()) {
                if (isset($this->processed[$code])) {
                    $this->tmpBuffer[$code] = $this->getNullsData($this->processed[$code]);
                    $this->persistentBuffer[$code] = $this->processed[$code];
                    $this->unsetBuffer($code);
                }
            } else {
                if (isset($this->processed[$code])) {
                    $this->buffer[$code] = $this->processed[$code];
                }
            }
            $this->bufferChildren($code);
        }
        throw new FlowRegulationException("", self::FLOW_CONTINUE);
    }

    /**
     * Buffer all child items
     * @param string $code
     */
    private function bufferChildren($code)
    {
        $lvl = $this->bag->getUnitLevel($code);
        $maxLvl = $this->bag->getLowestLevel();
        $lvl += 1;
        while ($lvl <= $maxLvl) {
            $codes = $this->bag->getUnitsFromLevel($lvl);
            foreach ($codes as $childCode) {
                if (!isset($this->buffer[$childCode]) && isset($this->processed[$childCode])) {
                    $this->buffer[$childCode] = $this->processed[$childCode];
                }
            }
            $lvl += 1;
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
                $code = $unit->getCode();
                if (!array_key_exists($code, $this->connectBuffer)) {
                    $this->analyzeOptionalItems($code);
                    $this->finished[] = $code;
                }
            }
            // checking if input has the correct # of mapped entries
            $intersect = array_intersect_key($this->connectBuffer, $this->buffer);
            foreach (array_keys($intersect) as $purgeKey) {
                $this->unsetBuffer($purgeKey);
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
     * Check the optional items missing
     * @param string $code
     * @throws FlowRegulationException
     */
    private function analyzeOptionalItems($code)
    {
        /** @var ExportFileUnitInterface $unit */
        $unit = $this->bag->getUnitByCode($code);
        if ($unit->isOptional()) {
            $allLasting = true;
            foreach ($this->buffer as $bufferedCode => $data) {
                $lasting = isset($this->lastingBuffer[$bufferedCode]) && $this->lastingBuffer[$bufferedCode];
                $allLasting &= $lasting;
                if ($lasting) {
                    $this->unsetBuffer($bufferedCode);
                }
            }
            if (!$allLasting && isset($this->lastProcessed[$code])) {
                $this->tmpBuffer[$code] = $this->getNullsData($this->lastProcessed[$code]);
                foreach ($this->buffer as $bufferedCode => $data) {
                    $this->tmpBuffer[$bufferedCode] = $this->buffer[$bufferedCode];
                    $this->unsetBuffer($bufferedCode);
                }
            } elseif ($allLasting) {
                $this->dumpWriteBuffer();
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
        $optionalCodeEncounter = false;
        foreach ($codes as $code) {
            /** @var ExportFileUnitInterface $unit */
            $unit = $this->bag->getUnitByCode($code);
            if (isset($this->buffer[$code]) && $unit->isOptional()) {
                $this->persistentBuffer[$code] = $this->buffer[$code];
                $this->tmpBuffer[$code] = $this->getNullsData($this->processed[$code]);
                $this->unsetBuffer($code);
                $optionalCodeEncounter = true;
                continue;
            }
        }
        // normal flow
        if (!$optionalCodeEncounter) {
            foreach ($codes as $code) {
                if ($this->cleanBuffer($code)) {
                    continue;
                }
                $this->fillBuffer($code);
            }
        }
        $this->dumpWriteBuffer();
        foreach ($this->bag as $unit) {
            $this->cleanPersistentBuffer($unit->getCode());
        }
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
        /** @var UnitInterface $singleUnit */
        foreach (array_merge($siblings, [$unit]) as $singleUnit) {
            $code = $singleUnit->getCode();
            $cleaned |= $this->unsetBuffer($code);
            // now check if buffer came from PS
            // in case it did, and we cleared because of conflict
            // we need to bring it back
            if (isset($this->toUnset[$code])) {
                unset($this->toUnset[$code]);
            }
        }
        return $cleaned;
    }

    /**
     * delete current buffer for given code
     * @param string $code
     * @return bool
     */
    private function unsetBuffer($code)
    {
        if (isset($this->buffer[$code])) {
            unset($this->buffer[$code]);
            return true;
        }
        return false;
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
        $this->map->clear();
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
            $code = $unit->getCode();
            if ($this->bag->isLowest($code)) {
                $this->unsetBuffer($code);
            }
            $this->cleanPersistentBuffer($code);
        }
        $this->writeBuffer = $this->assembleHierarchy($toAdd);
    }

    /**
     * @param string $code
     */
    private function cleanPersistentBuffer($code)
    {
        if (isset($this->toUnset[$code]) && isset($this->persistentBuffer[$code])) {
            unset($this->persistentBuffer[$code]);
        }
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
