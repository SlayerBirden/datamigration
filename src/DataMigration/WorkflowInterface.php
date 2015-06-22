<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Worker\WorkerBagInterface;

interface WorkflowInterface
{
    /**
     * @param string $code
     * @param WorkerBagInterface $bag
     * @return self
     */
    public function addAction($code, WorkerBagInterface $bag);

    /**
     * @param string $code
     * @return self
     */
    public function addChainedAction($code);

    /**
     * execute workflow
     */
    public function execute();
}
