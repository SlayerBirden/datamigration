<?php

namespace Maketok\DataMigration\Worker;

interface WorkerInterface
{
    /**
     * set main table worker is working with
     * @param string $tableName
     * @return self
     */
    public function setTable($tableName);

    /**
     * set condition that would determine if current row is Entity one for current worker
     * @param string $condition
     * @return self
     */
    public function setIsEntityCondition($condition);

    /**
     * add directive that should be executed for each row
     * @param string $contribution
     * @return self
     */
    public function addContribution($contribution);

    /**
     * add condition based on which it would be clear if we should add current entity
     * @param string $condition
     * @return self
     */
    public function addWriteCondition($condition);

    /**
     * set current mapping
     * @param array $mapping hashmap
     * @return self
     */
    public function setMapping(array $mapping);
}
