<?php

namespace Maketok\DataMigration\Worker\Type;

class DeleteWorker extends AbstractWorker
{
    /**
     * @var string[]|callable[]
     */
    protected $deleteConditions = [];

    /**
     * @param string|callable $condition
     * @return self
     */
    public function addDeleteCondition($condition)
    {
        $this->deleteConditions[] = $condition;
        return $this;
    }
}
