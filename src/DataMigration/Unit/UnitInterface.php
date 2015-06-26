<?php

namespace Maketok\DataMigration\Unit;

interface UnitInterface
{
    /**
     * set main table worker is working with
     * @param string $tableName
     * @return self
     */
    public function setTable($tableName);

    /**
     * set condition that would determine if current row is Entity for current worker
     * @param string|callable $condition
     * @return self
     */
    public function setIsEntityCondition($condition);

    /**
     * add directive that should be executed for each row
     * @param string|callable $contribution
     * @return self
     */
    public function addContribution($contribution);

    /**
     * add condition based on which it would be clear if we should add current entity
     * @param string|callable $condition
     * @return self
     */
    public function addWriteCondition($condition);

    /**
     * set current mapping
     * @param array|string[] $mapping hashmap
     * @return self
     */
    public function setMapping(array $mapping);

    /**
     * set Temporary file name generated for this unit
     * @param string $name
     * @return mixed
     */
    public function setTmpFileName($name);

    /**
     * get tmp file name
     * @return string
     */
    public function getTmpFileName();
}
