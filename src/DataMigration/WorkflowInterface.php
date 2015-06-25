<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Unit\UnitBagInterface;

interface WorkflowInterface
{
    /**
     * @param string $code
     * @param UnitBagInterface $bag
     * @return self
     */
    public function addAction($code, UnitBagInterface $bag);

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
