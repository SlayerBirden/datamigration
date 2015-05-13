<?php

namespace Maketok\DataMigration\Worker;

interface WorkerInterface
{
    /**
     * main execution flow
     */
    public function exec();
}
