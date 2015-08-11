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
                $this->map->freeze();
                foreach ($this->bag as $unit) {
                    list($max, $center) = $unit->getGenerationSeed();
                    $rnd = $this->getRandom($max, $center);
                    while ($rnd > 0) {
                        if (!empty($this->buffer)) {
                            $assembledBuffer = $this->assemble($this->buffer, true);
                            if ($this->map->isFresh($assembledBuffer)) {
                                $this->map->feed($assembledBuffer);
                            }
                        }
                        foreach ($unit->getContributions() as $contribution) {
                            $this->language->evaluate($contribution, [
                                'map' => $this->map,
                                'resource' => $this->helperResource,
                                'hashmaps' => $unit->getHashmaps(),
                            ]);
                        }
                        $row = array_map(function ($el) {
                            return $this->language->evaluate($el, [
                                'generator' => $this->generator,
                                'resource' => $this->helperResource,
                                'map' => $this->map,
                                'units' => $this->buffer,
                            ]);
                        }, $unit->getGeneratorMapping());
                        $this->buffer[$unit->getCode()] = $row;
                        $unit->getFilesystem()->writeRow($row);
                        $result->incrementActionProcessed($this->getCode());
                        $rnd--;
                    }
                }
                $this->map->unFreeze();
                $this->count--;
            }
        } catch (\Exception $e) {
            $this->close();
            throw $e;
        }
        $this->close();
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
