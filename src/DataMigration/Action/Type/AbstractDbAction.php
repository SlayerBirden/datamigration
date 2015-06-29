<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;
use Maketok\DataMigration\Storage\Db\ResourceInterface as DbResourceInterface;

class AbstractDbAction extends AbstractAction
{
    /**
     * @var DbResourceInterface
     */
    protected $resource;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param FsResourceInterface $filesystem
     * @param DbResourceInterface $resource
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                FsResourceInterface $filesystem,
                                DbResourceInterface $resource)
    {
        parent::__construct($bag, $config, $filesystem);
        $this->resource = $resource;
    }
}
