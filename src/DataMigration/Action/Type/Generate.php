<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
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
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     * @param int $count
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem,
                                $count)
    {
        parent::__construct($bag, $config, $filesystem);
        $this->count = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // TODO: Implement process() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'generate';
    }
}
