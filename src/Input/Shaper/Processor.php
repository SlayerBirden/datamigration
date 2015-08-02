<?php

namespace Maketok\DataMigration\Input\Shaper;

use Maketok\DataMigration\Action\Exception\ConflictException;
use Maketok\DataMigration\ArrayUtilsTrait;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Input\ShaperInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

class Processor implements ShaperInterface
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
        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $entity)
    {
        // TODO: Implement parse() method.
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
                $isEntity = $unit->getIsEntityCondition();
                if (!isset($this->oldmap) || empty($isEntity)) {
                    $shouldDump = true;
                } elseif (is_callable($isEntity) || is_string($isEntity)) {
                    $shouldDump = $this->language->evaluate($isEntity, [
                        'map' => $this->map,
                        'oldmap' => $this->oldmap,
                    ]);
                } else {
                    throw new \LogicException(
                        sprintf("Can not understand is Entity Condition for %s unit.", $unit->getCode())
                    );
                }
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
     * @param $row
     * @throws ConflictException
     */
    private function writeBuffered($row)
    {
        if (empty($row)) {
            return;
        }
        $level = $this->bag->getLowestLevel();
        $remembered = [];
        while ($level > 0) {
            $codes = $this->bag->getUnitsFromLevel($level);
            foreach ($codes as $code) {
                $children = $this->bag->getChildren($code);
                foreach ($children as $unit) {
                    $childCode = $unit->getCode();
                    if (isset($remembered[$level+1][$childCode])) {
                        if (isset($row[$childCode]) && !is_array($row[$childCode])) {
                            throw new ConflictException("Key to assign children to already exists.");
                        }
                        if (
                            isset($this->buffer[$code][$childCode]) &&
                            is_array($this->buffer[$code][$childCode])
                        ) {
                            $this->buffer[$code][$childCode][] = $remembered[$level+1][$childCode];
                        } else {
                            $this->buffer[$code][$childCode] = [$remembered[$level+1][$childCode]];
                        }
                    }
                }
                $remembered[$level][$code] = $row;
            }
            $level--;
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
        $this->map->clear();
    }
}
