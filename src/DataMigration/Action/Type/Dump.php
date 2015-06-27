<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface as DbResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

class Dump extends AbstractDbAction implements ActionInterface
{
    /**
     * @var InputResourceInterface
     */
    private $input;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param FsResourceInterface $filesystem
     * @param DbResourceInterface $resource
     * @param InputResourceInterface $input
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                FsResourceInterface $filesystem,
                                DbResourceInterface $resource,
                                InputResourceInterface $input)
    {
        parent::__construct($bag, $config, $filesystem, $resource);
        $this->input = $input;
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
        return 'dump';
    }
}
