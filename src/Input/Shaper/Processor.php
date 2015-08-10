<?php

namespace Maketok\DataMigration\Input\Shaper;

use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\ShaperInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

abstract class Processor implements ShaperInterface
{
    use ArrayUtilsTrait;

    /**
     * @var array
     */
    protected $buffer = [];
    /**
     * @var UnitBagInterface|ImportFileUnitInterface[]
     */
    protected $bag;
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * @var MapInterface
     */
    private $oldmap;
    /**
     * @var LanguageInterface
     */
    protected $language;

    /**
     * @param UnitBagInterface $bag
     * @param MapInterface $map
     * @param LanguageInterface $language
     */
    public function __construct(
        UnitBagInterface $bag,
        MapInterface $map,
        LanguageInterface $language
    ) {
        $this->bag = $bag;
        $this->map = $map;
        $this->language = $language;
        $this->bag->compileTree();
    }

    /**
     * {@inheritdoc}
     */
    public function feed(array $row)
    {
        if ($this->bag->getLowestLevel() == 1) {
            // no parent-child relationship
            if (!empty($row)) {
                return $row;
            }
            return false;
        }
        if ($this->map->isFresh($row)) {
            $this->map->feed($row);
        }
        // forcing dump if empty ros is coming
        $res = $this->dumpBuffer(empty($row));
        $this->writeBuffered($row);
        if ($res) {
            $this->map->clear();
        }
        return $res;
    }

    /**
     * @param bool $force
     * @return array|bool
     * @throws ConflictException
     */
    private function dumpBuffer($force = false)
    {
        $globalShouldDump = true;
        if (!$force) {
            foreach ($this->bag->getUnitsFromLevel(1) as $code) {
                /** @var ImportFileUnitInterface $unit */
                $unit = $this->bag->getUnitByCode($code);
                $shouldDump = $this->resolveIsEntity($unit);
                $globalShouldDump &= $shouldDump;
            }
        }
        if ($globalShouldDump) {
            if (!empty($this->buffer)) {
                $res = $this->assemble($this->buffer);
            } else {
                $res = false;
            }
            $this->clear();
            return $res;
        }
        return false;
    }

    /**
     * @param ImportFileUnitInterface $unit
     * @return bool|mixed
     */
    private function resolveIsEntity(ImportFileUnitInterface $unit)
    {
        $isEntity = $unit->getIsEntityCondition();
        if (!isset($this->oldmap) || empty($isEntity)) {
            return true;
        } elseif (is_callable($isEntity) || is_string($isEntity)) {
            return $this->language->evaluate($isEntity, [
                'map' => $this->map,
                'oldmap' => $this->oldmap,
            ]);
        } else {
            throw new \LogicException(
                sprintf("Can not understand is Entity Condition for %s unit.", $unit->getCode())
            );
        }
    }

    /**
     * @param $row
     * @throws ConflictException
     */
    private function writeBuffered($row)
    {
        if (empty($row)) {
            return;
        }
        $level = 1;
        $remembered = [];
        while ($level <= $this->bag->getLowestLevel()) {
            $codes = $this->bag->getUnitsFromLevel($level);
            foreach ($codes as $code) {
                if (isset($this->buffer[$code])) {
                    $remembered[$level][$code] = array_replace_recursive($this->buffer[$code], $row);
                } else {
                    $remembered[$level][$code] = $row;
                }
                /** @var ImportFileUnitInterface $unit */
                $unit = $this->bag->getUnitByCode($code);
                $parent = $unit->getParent();
                if ($parent) {
                    $parentCode = $parent->getCode();
                    if (isset($remembered[$level-1][$parentCode])) {
                        if (isset($row[$code]) && !is_array($row[$code])) {
                            throw new ConflictException("Key to assign children to already exists.");
                        }
                        if (
                            isset($remembered[$level-1][$parentCode][$code]) &&
                            is_array($remembered[$level-1][$parentCode][$code])
                        ) {
                            if ($this->resolveIsEntity($unit)) {
                                $remembered[$level-1][$parentCode][$code][] = &$remembered[$level][$code];
                            } else {
                                $remembered[$level][$code] = array_replace_recursive(
                                    $remembered[$level-1][$parentCode][$code][0],
                                    $remembered[$level][$code]
                                );
                                $remembered[$level-1][$parentCode][$code] = [&$remembered[$level][$code]];
                            }
                        } else {
                            $remembered[$level-1][$parentCode][$code] = [&$remembered[$level][$code]];
                        }
                    }
                }
            }
            $level++;
        }
        $this->buffer = array_replace_recursive($remembered[1], $this->buffer);
        $this->oldmap = clone $this->map;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->buffer = [];
    }
}
