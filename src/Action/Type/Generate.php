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
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     * @param Generator $generator
     * @param int $count
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem,
                                Generator $generator,
                                $count)
    {
        parent::__construct($bag, $config, $filesystem);
        $this->count = $count;
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
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
            $this->filesystem->open($unit->getTmpFileName(), 'w');
            while ($this->count > 0) {
                $row = array_map(function ($el) {
                    return call_user_func($el, $this->generator);
                }, $unit->getGeneratorMapping());
                $this->filesystem->writeRow($row);
                $this->count--;
            }
            $this->filesystem->close();
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
