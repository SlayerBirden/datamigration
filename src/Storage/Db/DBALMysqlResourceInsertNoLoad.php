<?php

namespace Maketok\DataMigration\Storage\Db;

class DBALMysqlResourceInsertNoLoad extends DBALMysqlResource
{
    /**
     * {@inheritdoc}
     * override, add support for inserts
     * if server does not support Local Infile option
     */
    public function loadData(
        $table,
        $file,
        $local = false,
        array $columns = [],
        array $set = [],
        $delimiter = ",",
        $enclosure = '"',
        $escape = '\\',
        $termination = '\n',
        $optionallyEnclosed = true
    ) {
        $csv = new \SplFileObject($file, 'r');
        $table = $this->connection->quoteIdentifier($table);
        $columnsPart = '';
        if (!empty($columns)) {
            $columns = array_map([$this->connection, 'quoteIdentifier'], $columns);
            $columnsPart = '(' . implode(',', $columns) . ')';
        }
        $valuesPart = '';
        $row = $csv->fgetcsv($delimiter, $enclosure, $escape);
        $csv->rewind();
        if (!empty($row)) {
            $row = array_map(function () {
                return '?';
            }, $row);
            $valuesPart = '(' . implode(',', $row) . ')';
        }
        $sql = <<<MYSQL
INSERT INTO $table $columnsPart
VALUES $valuesPart
MYSQL;
        $count = 0;
        while (
            ($row = $csv->fgetcsv($delimiter, $enclosure, $escape)) !== null &&
            $row != [null] &&
            $row !== false
        ) {
            $row = array_map(function ($var) {
                if ($var === '\N') {
                    return null;
                }
                return $var;
            }, $row);
            $count += $this->connection->executeUpdate($sql, $row);
        }
        return $count;
    }
}
