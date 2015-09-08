<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Unit\GenerateUnitInterface;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Unit\UnitInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Generate data and insert into tmp files
 */
class Generate extends AbstractAction implements ActionInterface
{
    use ArrayUtilsTrait;

    /**
     * @var UnitBagInterface|GenerateUnitInterface[]|ImportFileUnitInterface[]
     */
    protected $bag;
    /**
     * @var int
     */
    private $count;
    /**
     * @var Generator
     */
    private $generator;
    /**
     * @var array
     */
    private $buffer = [];
    /**
     * @var array
     */
    private $writeBuffer = [];
    /**
     * @var LanguageInterface
     */
    private $language;
    /**
     * @var ResultInterface
     */
    protected $result;
    /**
     * @var ResourceHelperInterface
     */
    private $helperResource;
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * @var array
     */
    private $processedCounterContainer = [];
    /**
     * @var array
     */
    private $contributionBuffer = [];
    /**
     * Units that have been processed this iteration
     * @var array
     */
    private $processedUnits = [];

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param LanguageInterface $language
     * @param Generator $generator
     * @param int $count
     * @param MapInterface $map
     * @param ResourceHelperInterface $helperResource
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        Generator $generator,
        $count,
        MapInterface $map,
        ResourceHelperInterface $helperResource
    ) {
        parent::__construct($bag, $config);
        $this->count = $count;
        $this->generator = $generator;
        $this->language = $language;
        $this->helperResource = $helperResource;
        $this->map = $map;
    }

    /**
     * {@inheritdoc}
     * @throws WrongContextException
     * @throws \LogicException
     */
    public function process(ResultInterface $result)
    {
        $this->result = $result;
        try {
            $this->start();
            while ($this->count > 0) {
                foreach ($this->bag as $unit) {
                    if (in_array($unit->getCode(), $this->processedUnits)) {
                        continue;
                    }
                    list($max, $center) = $unit->getGenerationSeed();
                    $rnd = $this->getRandom($max, $center);
                    $i = 0;
                    while ($rnd > 0) {
                        $siblings = $unit->getSiblings();
                        array_unshift($siblings, $unit);
                        /** @var GenerateUnitInterface|ImportFileUnitInterface $innerUnit */
                        foreach ($siblings as $innerUnit) {
                            $this->processAdditions($innerUnit, $i);
                            if (!$this->shouldWrite($innerUnit)) {
                                $this->processedUnits[] = $innerUnit->getCode();
                                continue 1;
                            }
                            $row = $this->getMappedRow($innerUnit);
                            // we care about parent ;)
                            /** @var ImportFileUnitInterface|GenerateUnitInterface $parent */
                            if ($parent = $innerUnit->getParent()) {
                                $this->updateParents($parent);
                            }
                            // freeze map after 1st addition
                            $this->map->freeze();
                            $this->buffer[$innerUnit->getCode()] = $row;
                            $this->processedUnits[] = $innerUnit->getCode();
                            $this->writeBuffered($innerUnit->getCode(), $row);
                            $this->count($innerUnit);
                        }
                        $rnd--;
                        $i++;
                    }
                }
                $this->contributionBuffer = [];
                $this->processedCounterContainer = [];
                $this->writeRows();
                $this->map->unFreeze();
                $this->count--;
                $this->processedUnits = [];
                $this->buffer = [];
            }
        } catch (\Exception $e) {
            $this->close();
            throw $e;
        }
        $this->close();
    }

    /**
     * @param GenerateUnitInterface|ImportFileUnitInterface $unit
     * @param int $idx
     */
    protected function processAdditions(GenerateUnitInterface $unit, $idx = 0)
    {
        if (isset($this->contributionBuffer[$idx])) {
            $cBuffer = $this->contributionBuffer[$idx];
        } else {
            $this->contributionBuffer[$idx] = [];
            $cBuffer = [];
        }
        if (!in_array($unit->getCode(), $cBuffer)) {
            foreach ($unit->getGenerationContributions() as $contribution) {
                $this->language->evaluate($contribution, [
                    'generator' => $this->generator,
                    'map' => $this->map,
                    'resource' => $this->helperResource,
                    'hashmaps' => $unit->getHashmaps(),
                ]);
            }
            $this->contributionBuffer[$idx][] = $unit->getCode();
        }

        /** @var GenerateUnitInterface|ImportFileUnitInterface $sibling */
        foreach ($unit->getSiblings() as $sibling) {
            if (!in_array($sibling->getCode(), $cBuffer)) {
                foreach ($sibling->getGenerationContributions() as $contribution) {
                    $this->language->evaluate($contribution, [
                        'generator' => $this->generator,
                        'map' => $this->map,
                        'resource' => $this->helperResource,
                        'hashmaps' => $sibling->getHashmaps(),
                    ]);
                }
                $this->contributionBuffer[$idx][] = $sibling->getCode();
            }
        }
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @return bool
     */
    protected function shouldWrite(ImportFileUnitInterface $unit)
    {
        foreach ($unit->getWriteConditions() as $condition) {
            $shouldAdd = $this->language->evaluate($condition, [
                'generator' => $this->generator,
                'map' => $this->map,
                'resource' => $this->helperResource,
                'hashmaps' => $unit->getHashmaps(),
            ]);
            if (!$shouldAdd) {
               return false;
            }
        }
        return true;
    }

    /**
     * @param UnitInterface $unit
     */
    protected function count(UnitInterface $unit)
    {
        if (!$unit->getParent() && !in_array($unit->getCode(), $this->processedCounterContainer)) {
            $this->result->incrementActionProcessed($this->getCode());
            $this->processedCounterContainer[] = $unit->getCode();
            foreach ($unit->getSiblings() as $sibling) {
                $this->processedCounterContainer[] = $sibling->getCode();
            }
        }
    }

    /**
     * @param GenerateUnitInterface|ImportFileUnitInterface $unit
     * @return array
     */
    protected function getMappedRow(GenerateUnitInterface $unit)
    {
        return array_map(function ($el) use ($unit) {
            return $this->language->evaluate($el, [
                'generator' => $this->generator,
                'resource' => $this->helperResource,
                'map' => $this->map,
                'units' => $this->buffer,
                'hashmaps' => $unit->getHashmaps(),
            ]);
        }, $unit->getGeneratorMapping());
    }

    /**
     * prepare map
     * @deprecated do not use
     */
    protected function prepareMap()
    {
        if (!empty($this->buffer)) {
            $assembledBuffer = $this->assembleResolve($this->buffer);
            if ($this->map->isFresh($assembledBuffer)) {
                $this->map->feed($assembledBuffer);
            }
        }
    }

    /**
     * @param GenerateUnitInterface|ImportFileUnitInterface $parent
     */
    protected function updateParents(GenerateUnitInterface $parent)
    {
        $parentRow = array_map(function ($el) use ($parent) {
            return $this->language->evaluate($el, [
                'generator' => $this->generator,
                'resource' => $this->helperResource,
                'map' => $this->map,
                'units' => $this->buffer,
                'hashmaps' => $parent->getHashmaps(),
            ]);
        }, $parent->getGeneratorMapping());
        if ($this->shouldWrite($parent)) {
            $this->buffer[$parent->getCode()] = $parentRow;
            $this->writeBuffered($parent->getCode(), $parentRow, true);
        }
        // account for parent siblings :)
        /** @var GenerateUnitInterface|ImportFileUnitInterface $sibling */
        foreach ($parent->getSiblings() as $sibling) {
            $siblingRow = array_map(function ($el) use ($sibling) {
                return $this->language->evaluate($el, [
                    'generator' => $this->generator,
                    'resource' => $this->helperResource,
                    'map' => $this->map,
                    'units' => $this->buffer,
                    'hashmaps' => $sibling->getHashmaps(),
                ]);
            }, $sibling->getGeneratorMapping());
            if ($this->shouldWrite($sibling)) {
                $this->buffer[$sibling->getCode()] = $siblingRow;
                $this->writeBuffered($sibling->getCode(), $siblingRow, true);
            }
        }
    }

    /**
     * @param string $unitCode
     * @param array $row
     * @param bool $replace
     */
    protected function writeBuffered($unitCode, $row, $replace = false)
    {
        if (isset($this->writeBuffer[$unitCode]) && is_array($this->writeBuffer[$unitCode])) {
            if ($replace) {
                // pop last element into void
                array_pop($this->writeBuffer[$unitCode]);
            }
            $this->writeBuffer[$unitCode][] = $row;
        } else {
            $this->writeBuffer[$unitCode] = [$row];
        }
    }

    /**
     * write buffered rows
     */
    protected function writeRows()
    {
        foreach ($this->bag as $unit) {
            if (isset($this->buffer[$unit->getCode()])) {
                $buffered = $this->writeBuffer[$unit->getCode()];
                foreach ($buffered as $row) {
                    $unit->getFilesystem()->writeRow($row);
                }
            }
        }
        $this->writeBuffer = [];
    }

    /**
     * @param int $max
     * @param int $center
     * @param int $min
     * @return int
     * @throws \LogicException
     */
    public function getRandom($max, $center, $min = 1)
    {
        $min = (int) $min;
        $max = (int) $max;
        $center = (int) $center;
        if ($min == $max) {
            return $min;
        }
        if ($min > $max || $min > $center || $center > $max) {
            throw new \LogicException("Wrong values given.");
        }
        // get 1/8
        $period = (int) ceil($max/8);
        $pMin = max($min, $center - $period);
        $pMax = min($center+$period, $max);
        $s1 = mt_rand(0, 1);
        if ($s1 == 0) {
            return mt_rand($pMin, $pMax);
        } else {
            $s2 = mt_rand(0, 1);
            if (0 == $s2) {
                return mt_rand($min, $pMin);
            } else {
                return mt_rand($pMax, $max);
            }
        }
    }

    /**
     * open handlers
     * @throws WrongContextException
     */
    private function start()
    {
        $this->result->setActionStartTime($this->getCode(), new \DateTime());
        foreach ($this->bag as $unit) {
            if ($unit->getGeneratorMapping() === null) {
                throw new WrongContextException(
                    sprintf(
                        "Can not use generation with unit %s. No generation mapping found.",
                        $unit->getCode()
                    )
                );
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
        return 'generate';
    }
}
