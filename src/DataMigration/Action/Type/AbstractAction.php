<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
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
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     */
    public function __construct(UnitBagInterface $bag, ConfigInterface $config)
    {
        $this->bag = $bag;
        $this->config = $config;
    }
}
