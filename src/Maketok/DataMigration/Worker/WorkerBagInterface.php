<?php

namespace Maketok\DataMigration\Worker;

interface WorkerBagInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param WorkerInterface $worker
     */
    public function addWorker(WorkerInterface $worker);

    /**
     * @param array|WorkerInterface[] $workers
     */
    public function addWorkers(array $workers);
}
