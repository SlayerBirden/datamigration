<?php

namespace Maketok\DataMigration\Unit;

interface ImportDbUnitInterface extends ImportFileUnitInterface
{
    /**
     * set main table unit is working with
     * @param string $tableName
     * @return void
     */
    public function setTable($tableName);

    /**
     * get main table name
     * @return string
     */
    public function getTable();

    /**
     * set Temporary table name generated for this unit
     * @param string $name
     * @return void
     */
    public function setTmpTable($name);

    /**
     * get tmp table name
     * @return string
     */
    public function getTmpTable();

    /**
     * Definition of PK for current unit/table: can be sting or array
     * @param mixed $definition
     * @return void
     */
    public function setPk($definition);

    /**
     * Get PK def
     * @return mixed
     */
    public function getPk();
}
