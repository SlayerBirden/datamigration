<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

class AbstractAction
{
    /**
     * @var UnitBagInterface
     */
    protected $bag;
    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var ResourceInterface
     */
    protected $filesystem;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem)
    {
        $this->bag = $bag;
        $this->config = $config;
        $this->filesystem = $filesystem;
    }
}
