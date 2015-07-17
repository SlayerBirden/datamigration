<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Expression\LanguageInterface;
use Maketok\DataMigration\Unit\GenerateUnitInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

/**
 * Generate data and insert into tmp files
 */
class Generate extends AbstractAction implements ActionInterface
{
    /**
     * @var UnitBagInterface|GenerateUnitInterface[]
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
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param LanguageInterface $language
     * @param Generator $generator
     * @param int $count
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        LanguageInterface $language,
        Generator $generator,
        $count
    ) {
        parent::__construct($bag, $config);
        $this->count = $count;
        $this->generator = $generator;
        $this->language = $language;
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
                    list($max, $center) = $unit->getGenerationSeed();
                    $rnd = $this->getRandom($max, $center);
                    while ($rnd > 0) {
                        $row = array_map(function ($el) {
                            return $this->language->evaluate($el, [
                                'generator' => $this->generator,
                                'units' => $this->buffer,
                            ]);
                        }, $unit->getGeneratorMapping());
                        $this->buffer[$unit->getCode()] = $row;
                        $unit->getFilesystem()->writeRow($row);
                        $result->incrementActionProcessed($this->getCode());
                        $rnd--;
                    }
                }
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
