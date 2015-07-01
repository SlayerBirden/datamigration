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
     * @param string|string[] $pk
     * @return bool
     */
    public function deleteUsingTempPK($deleteTable, $tmpTable, $pk = 'id');

    /**
     * Load data from file to table
     * @param string $table
     * @param string $file
     * @param bool $local
     * @param array $columns
     * @param array $set
     * @return mixed
     */
    public function loadData($table,
                             $file,
                             $local = false,
                             array $columns = null,
                             array $set = null);

    /**
     * Move specified columns data from table a to table b using conditions
     * @param string $fromTable
     * @param string $toTable
     * @param array $columns
     * @param array $conditions
     * @return mixed
     */
    public function move($fromTable, $toTable, array $columns = null, array $conditions = null);

    /**
     * Dump selected columns data to array
     * @param string $table
     * @param array $columns
     * @param int $limit
     * @param int $offset
     * @return array|false
     */
    public function dumpData($table, array $columns = null, $limit = 1000, $offset = 0);

    /**
     * @param string $name
     * @param array $columns
     * @return bool
     */
    public function createTmpTable($name, array $columns);
}
