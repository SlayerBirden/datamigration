<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface as DbResourceInterface;

/**
 * Move data from one db table to another
 */
class Move extends AbstractDbAction implements ActionInterface
{
    /**
     * @var string
     */
    private $from;
    /**
     * @var string
     */
    private $to;
    /**
     * @var array
     */
    private $columns;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param FsResourceInterface $filesystem
     * @param DbResourceInterface $resource
     * @param string $from
     * @param string $to
     * @param array $columns
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                FsResourceInterface $filesystem,
                                DbResourceInterface $resource,
                                $from,
                                $to,
                                array $columns)
    {
        parent::__construct($bag, $config, $filesystem, $resource);
        $this->from = $from;
        $this->to = $to;
        $this->columns = $columns;
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
        return 'move';
    }
}
