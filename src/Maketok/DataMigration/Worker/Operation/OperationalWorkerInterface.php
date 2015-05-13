<?php

namespace Maketok\DataMigration\Worker\Operation;

use Maketok\DataMigration\Worker\WorkerInterface;

interface OperationalWorkerInterface extends WorkerInterface
{
    public function addOperation(OperationInterface $operation);
}
