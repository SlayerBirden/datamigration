<?php

namespace Maketok\DataMigration\Worker;

interface WorkerBagInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param WorkerInterface $worker
     */
    public function add(WorkerInterface $worker);
}
