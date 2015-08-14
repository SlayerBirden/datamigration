<?php

namespace Maketok\DataMigration\Storage\Db;

interface ResourceInterface
{
    /**
     * Open connection to DB
     * @return mixed
     */
    public function open();

    /**
     * Close connection to DB
     * @return mixed
     */
    public function close();

    /**
     * Delete rows in table using PK from temp table
     * @param string $deleteTable
     * @param string $tmpTable
     * @param string|string[] $primaryKey
     * @return int number of rows deleted
     */
    public function deleteUsingTempPK($deleteTable, $tmpTable, $primaryKey = 'id');

    /**
     * Load data from file to table
     * @param string $table
     * @param string $file
     * @param bool $local
     * @param array $columns
     * @param array $set
     * @return int number of rows loaded
     */
    public function loadData(
        $table,
        $file,
        $local = false,
        array $columns = [],
        array $set = []
    );

    /**
     * Move specified columns data from table a to table b using conditions
     * @param string $fromTable
     * @param string $toTable
     * @param array $columns
     * @param array $conditions
     * @param array $orderBy
     * @param string $dir
     * @return int number of rows moved
     */
    public function move(
        $fromTable,
        $toTable,
        array $columns = [],
        array $conditions = [],
        array $orderBy = [],
        $dir = 'ASC'
    );

    /**
     * Dump selected columns data to array
     * @param string $table
     * @param array $columns
     * @param int $limit
     * @param int $offset
     * @return array|false
     */
    public function dumpData($table, array $columns = [], $limit = 1000, $offset = 0);

    /**
     * @param string $name
     * @param array $columns
     * @return bool
     */
    public function createTmpTable($name, array $columns);

    /**
     * Start transaction
     * @return void
     */
    public function startTransaction();

    /**
     * Commit transaction
     * @return void
     */
    public function commit();

    /**
     * Rollback transaction
     * @return void
     */
    public function rollback();

    /**
     * Is connection active
     * @return bool
     */
    public function isActive();
}
