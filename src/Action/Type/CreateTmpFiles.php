<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\NormalizationException;
use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Exception\ParsingException;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Disperse base input stream into separate units (tmp csv files) for further processing
 */
class CreateTmpFiles extends AbstractAction implements ActionInterface
{
    use ArrayUtilsTrait;

    /**
     * @var UnitBagInterface|ImportFileUnitInterface[]
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
     * @var ResourceHelperInterface
     */
    private $helperResource;
    /**
     * @var array
     */
    private $buffer = [];
    /**
     * @var array
     */
    private $contributionBuffer = [];
    /**
     * is last processed entity valid
     * @var bool
     */
    private $isValid = true;
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
     * @param ResourceHelperInterface $helperResource
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        InputResourceInterface $input,
        MapInterface $map,
        ResourceHelperInterface $helperResource
    ) {
        parent::__construct($bag, $config);
        $this->input = $input;
        $this->map = $map;
        $this->helperResource = $helperResource;
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException
     */
    public function process(ResultInterface $result)
    {
        $this->result = $result;
        $this->start();
        while (true) {
            try {
                $entity = $this->input->get();
                if ($entity === false) {
                    break 1;
                }
                $this->processWrite($entity);
                $this->contributionBuffer = [];
                $this->dumpBuffer();
            } catch (ParsingException $e) {
                if ($this->config['continue_on_error']) {
                    $this->result->addActionException($this->getCode(), $e);
                } else {
                    $this->close();
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->close();
                throw $e;
            }
        }
        $this->dumpBuffer();
        $this->close();
    }

    /**
     * write row to buffer
     * @param array $entity
     * @param int $level
     * @param int $idx
     */
    private function processWrite(array $entity, $level = 1, $idx = 0)
    {
        $this->isValid = true;
        // parsing entity according to the relation tree
        $topUnits = $this->bag->getUnitsFromLevel($level);
        if (!isset($this->contributionBuffer[$level][$idx])) {
            $this->contributionBuffer[$level] = [$idx => []];
        }
        foreach ($topUnits as $code) {
            if ($this->map->isFresh($entity)) {
                $this->map->setState($entity);
            }
            /** @var ImportFileUnitInterface $unit */
            $unit = $this->bag->getUnitByCode($code);
            $this->processAdditions($unit, $idx);
            if (!$this->validate($unit)) {
                $this->isValid = false;
            }
            $this->writeRowBuffered($unit);
            $children = $this->bag->getChildren($code);
            foreach ($children as $child) {
                if (isset($entity[$child->getCode()])) {
                    $childData = $entity[$child->getCode()];
                    $i = 0;
                    foreach ($childData as $childEntity) {
                        $this->processWrite($childEntity, $this->bag->getUnitLevel($child->getCode()), $i);
                        $i++;
                    }
                }
            }
        }
    }

    /**
     * open handlers
     */
    private function start()
    {
        $this->result->setActionStartTime($this->getCode(), new \DateTime());
        $this->bag->compileTree();
        foreach ($this->bag as $unit) {
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
        $this->input->reset();
        $this->result->setActionEndTime($this->getCode(), new \DateTime());
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @param int $idx
     */
    private function processAdditions(ImportFileUnitInterface $unit, $idx = 0)
    {
        $level = $this->bag->getUnitLevel($unit->getCode());
        if (isset($this->contributionBuffer[$level][$idx])) {
            $contributionBuffer = $this->contributionBuffer[$level][$idx];
        } else {
            $this->contributionBuffer[$level] = [$idx => []];
            $contributionBuffer = [];
        }
        if (!in_array($unit->getCode(), $contributionBuffer)) {
            foreach ($unit->getContributions() as $contribution) {
                $this->language->evaluate($contribution, [
                    'map' => $this->map,
                    'resource' => $this->helperResource,
                    'hashmaps' => $unit->getHashmaps(),
                ]);
            }
            $this->contributionBuffer[$level][$idx][] = $unit->getCode();
        }

        /** @var ImportFileUnitInterface $sibling */
        foreach ($unit->getSiblings() as $sibling) {
            if (!in_array($sibling->getCode(), $contributionBuffer)) {
                foreach ($sibling->getContributions() as $contribution) {
                    $this->language->evaluate($contribution, [
                        'map' => $this->map,
                        'resource' => $this->helperResource,
                        'hashmaps' => $sibling->getHashmaps(),
                    ]);
                }
                $this->contributionBuffer[$level][$idx][] = $sibling->getCode();
            }
        }
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @param bool $replace
     */
    private function writeRowBuffered(ImportFileUnitInterface $unit, $replace = false)
    {
        $shouldAdd = true;
        foreach ($unit->getWriteConditions() as $condition) {
            $shouldAdd = $this->language->evaluate($condition, [
                'map' => $this->map,
                'resource' => $this->helperResource,
                'hashmaps' => $unit->getHashmaps(),
            ]);
            if (!$shouldAdd) {
                break 1;
            }
        }
        if ($shouldAdd) {
            $row = array_map(function ($var) use ($unit) {
                return $this->language->evaluate($var, [
                    'map' => $this->map,
                    'resource' => $this->helperResource,
                    'hashmaps' => $unit->getHashmaps(),
                ]);
            }, $unit->getMapping());
            /**
             * Each unit can return rows multiple times in case it needs to
             * but each mapped part should be returned equal times
             */
            try {
                if (isset($this->buffer[$unit->getCode()]) && is_array($this->buffer[$unit->getCode()])) {
                    if ($replace) {
                        // replace last row in the buffer
                        array_pop($this->buffer[$unit->getCode()]);
                    }
                    $this->buffer[$unit->getCode()] = array_merge(
                        $this->buffer[$unit->getCode()],
                        $this->normalize($row)
                    );
                } else {
                    $this->buffer[$unit->getCode()] = $this->normalize($row);
                }
            } catch (NormalizationException $e) {
                $this->result->addActionException($this->getCode(), $e);
            }
            /** @var ImportFileUnitInterface $parent */
            if ($parent = $unit->getParent()) {
                $this->writeRowBuffered($parent, true);
                $siblings = $parent->getSiblings();
                /** @var ImportFileUnitInterface $sibling */
                foreach ($siblings as $sibling) {
                    $this->writeRowBuffered($sibling, true);
                }
            }
        }
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @return bool
     */
    private function validate(ImportFileUnitInterface $unit)
    {
        $valid = true;
        foreach ($unit->getValidationRules() as $validationRule) {
            $valid = $this->language->evaluate($validationRule, [
                'map' => $this->map,
                'resource' => $this->helperResource,
                'hashmaps' => $unit->getHashmaps(),
            ]);
            if (!$valid) {
                $this->result->addActionError(
                    $this->getCode(),
                    sprintf(
                        "Invalid row %s for unit %s.",
                        json_encode($this->map->dumpState()),
                        $unit->getCode()
                    )
                );
                break 1;
            }
        }
        return (bool) $valid;
    }

    /**
     * @param ImportFileUnitInterface $unit
     */
    private function dumpBuffer(ImportFileUnitInterface $unit = null)
    {
        if (!$this->isValid && !$this->config['ignore_validation']) {
            return;
        }
        $processedCounterContainer = [];
        foreach ($this->buffer as $key => $dataArray) {
            if ($unit && $key != $unit->getCode()) {
                continue;
            }
            $handler = false;
            $tmpUnit = false;
            if ($unit) {
                $handler = $unit->getFilesystem();
            } elseif (($tmpUnit = $this->bag->getUnitByCode($key)) !== false) {
                /** @var ImportFileUnitInterface $tmpUnit */
                $handler = $tmpUnit->getFilesystem();
            }
            if (is_object($handler)) {
                foreach ($dataArray as $row) {
                    $written = $handler->writeRow(array_values($row));
                    if (false === $written) {
                        $this->result->addActionError(
                            $this->getCode(),
                            sprintf("Could not write row %s to file.", json_encode($row))
                        );
                    } elseif ($tmpUnit && !$tmpUnit->getParent() && !in_array($tmpUnit->getCode(), $processedCounterContainer)) {
                        $this->result->incrementActionProcessed($this->getCode());
                        $processedCounterContainer[] = $tmpUnit->getCode();
                        foreach ($tmpUnit->getSiblings() as $sibling) {
                            $processedCounterContainer[] = $sibling->getCode();
                        }
                    }
                }
            }
            unset($this->buffer[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'create_tmp_files';
    }
}
