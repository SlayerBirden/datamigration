<?php

namespace Maketok\DataMigration\Worker\Operation;

interface OperationInterface
{
    /**
     * run the operation
     */
    public function run();
}
