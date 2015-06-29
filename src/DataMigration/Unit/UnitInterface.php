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

    /**
     * set Temporary table name generated for this unit
     * @param string $name
     * @return mixed
     */
    public function setTmpTable($name);

    /**
     * get tmp table name
     * @return string
     */
    public function getTmpTable();

    /**
     * get main table name
     * @return string
     */
    public function getTable();

    /**
     * Definition of PK for current unit/table: can be sting or array
     * @param mixed $definition
     * @return self
     */
    public function setPk($definition);

    /**
     * Get PK def
     * @return mixed
     */
    public function getPk();
}
