<?php

namespace Maketok\DataMigration\Action\Type;

use Faker\Generator;
use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

/**
 * Generate data and insert into tmp files
 */
class Generate extends AbstractAction implements ActionInterface
{
    /**
     * @var int
     */
    private $count;
    /**
     * @var Generator
     */
    private $generator;
    /**
     * @var ResourceInterface[]
     */
    private $handlers = [];
    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param Generator $generator
     * @param int $count
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config,
        Generator $generator,
        $count
    ) {
        parent::__construct($bag, $config);
        $this->count = $count;
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     * @throws WrongContextException, \LogicException
     */
    public function process()
    {
        $this->start();
        while ($this->count > 0) {
            foreach ($this->bag as $unit) {
                list($max, $center) = $unit->getGenerationSeed();
                $rnd = $this->getRandom($max, $center);
                while ($rnd > 0) {
                    $row = array_map(function ($el) {
                        if (is_callable($el)) {
                            return call_user_func_array($el, [
                                'generator' => $this->generator,
                                'units' => $this->buffer,
                            ]);
                        } else {
                            return $el;
                        }
                    }, $unit->getGeneratorMapping());
                    $this->buffer[$unit->getTable()] = $row;
                    $this->filesystem->writeRow($row);
                    $rnd--;
                }
            }
            $this->count--;
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
        $s1 = mt_rand(0,1);
        if ($s1 == 0) {
            return mt_rand($pMin, $pMax);
        } else {
            $s2 = mt_rand(0,1);
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
        foreach ($this->bag as $unit) {
            if ($unit->getGeneratorMapping() === null) {
                throw new WrongContextException(
                    sprintf(
                        "Can not use generation with unit %s. No generation mapping found.",
                        $unit->getTable()
                    )
                );
            }
            $unit->setTmpFileName($this->getTmpFileName($unit));
            $handler = clone $this->filesystem;
            $handler->open($unit->getTmpFileName(), 'w');
            $this->handlers[$unit->getTable()] = $handler;
        }
    }

    /**
     * close all handlers
     */
    private function close()
    {
        foreach ($this->bag as $unit) {
            $handler = $this->handlers[$unit->getTable()];
            $handler->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'generate';
    }
}
