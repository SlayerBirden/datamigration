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
     * Delete rows in table using conditions
     * @param string $table
     * @param array $condition
     * @return bool
     */
    public function delete($table, array $condition);

    /**
     * Get last increment value for given table
     * @param string $table
     * @return mixed
     */
    public function getLastIncrement($table);

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
     * Dump selected columns data to file
     * @param string $table
     * @param string $file
     * @param array $columns
     * @return mixed
     */
    public function dumpData($table, $file, array $columns = null);
}
