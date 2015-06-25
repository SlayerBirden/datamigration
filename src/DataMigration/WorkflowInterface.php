<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

interface WorkflowInterface
{
    /**
     * @param string $code
     * @param ConfigInterface $config
     * @param UnitBagInterface $bag
     * @return self
     */
    public function addAction($code, ConfigInterface $config, UnitBagInterface $bag);

    /**
     * add chained action which will operate the latest stacked bag
     *
     * @param string $code
     * @return self
     */
    public function addChainedAction($code);

    /**
     * execute workflow
     */
    public function execute();
}
