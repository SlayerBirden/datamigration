<?php

namespace Maketok\DataMigration\Storage\Db;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\DBAL\Schema\Schema;

class DBALMysqlResource extends AbstractDBALResource
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * {@inheritdoc}
     */
    public function deleteUsingTempPK($deleteTable, $tmpTable, $primaryKey = 'id')
    {
        $sql = $this->getDeleteUsingTempPkSql($deleteTable, $tmpTable, $primaryKey);
        return $this->connection->executeUpdate($sql);
    }

    /**
     * @param string $deleteTable
     * @param string $tmpTable
     * @param string|string[] $primaryKey
     * @return string
     */
    public function getDeleteUsingTempPkSql($deleteTable, $tmpTable, $primaryKey)
    {
        $deleteTable = $this->connection->quoteIdentifier($deleteTable);
        $tmpTable = $this->connection->quoteIdentifier($tmpTable);
        if (!is_array($primaryKey)) {
            $primaryKey = [$primaryKey];
        }
        $primaryKey = array_map([$this->connection, 'quoteIdentifier'], $primaryKey);
        $conditionParts = [];
        foreach ($primaryKey as $key) {
            $conditionParts[] = "`main_table`.$key=`tmp_table`.$key";
        }
        $condition = implode('AND', $conditionParts);
        return <<<MYSQL
DELETE main_table FROM $deleteTable AS main_table
JOIN $tmpTable AS tmp_table ON $condition
MYSQL;
    }

    /**
     * {@inheritdoc}
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param string $termination
     * @param bool $optionallyEnclosed
     */
    public function loadData(
        $table,
        $file,
        $local = false,
        array $columns = [],
        array $set = [],
        $delimiter = ",",
        $enclosure = '"',
        $escape = "\\",
        $termination = "\n",
        $optionallyEnclosed = true
    ) {
        $sql = $this->getLoadDataSql($table, $file, $local, $columns, $set, $delimiter, $enclosure,
            $escape, $termination, $optionallyEnclosed);
        return $this->connection->executeUpdate($sql);
    }

    /**
     * @param string $table
     * @param string $file
     * @param bool|false $local
     * @param array $columns
     * @param array $set
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param string $termination
     * @param bool|true $optionallyEnclosed
     * @return string
     */
    public function getLoadDataSql(
        $table,
        $file,
        $local = false,
        array $columns = [],
        array $set = [],
        $delimiter = ",",
        $enclosure = '"',
        $escape = "\\",
        $termination = "\n",
        $optionallyEnclosed = true
    ) {
        $localKey = $local ? 'LOCAL' : '';
        $table = $this->connection->quoteIdentifier($table);
        $optionalKey = $optionallyEnclosed ? 'OPTIONALLY' : '';
        if (!empty($columns)) {
            $columns = '(' . implode(',', $columns) . ')';
        } else {
            $columns = '';
        }
        if (!empty($set)) {
            $setParts = [];
            foreach ($set as $key => $val) {
                $setParts[] = "$key=$val";
            }
            $set = 'SET ' . implode(',', $setParts);
        } else {
            $set = '';
        }
        return <<<MYSQL
LOAD DATA $localKey INFILE '$file'
INTO TABLE $table
FIELDS
    TERMINATED BY '$delimiter'
    $optionalKey ENCLOSED BY '$enclosure'
    ESCAPED BY '$escape'
LINES
    TERMINATED BY '$termination'
$columns
$set
MYSQL;
    }

    /**
     * {@inheritdoc}
     */
    public function move(
        $fromTable,
        $toTable,
        array $columns = [],
        array $conditions = [],
        array $orderBy = [],
        $dir = 'ASC'
    ) {
        $sql = $this->getMoveSql($fromTable, $toTable, $columns, $conditions, $orderBy, $dir);
        return $this->connection->executeUpdate($sql);
    }

    /**
     * @param string $fromTable
     * @param string $toTable
     * @param array $columns
     * @param array $conditions
     * @param array $orderBy
     * @param string $dir
     * @return string
     */
    public function getMoveSql(
        $fromTable,
        $toTable,
        array $columns = [],
        array $conditions = [],
        array $orderBy = [],
        $dir = 'ASC'
    ) {
        $selectColumns = '*';
        $onDuplicate = '';
        $fromTable = $this->connection->quoteIdentifier($fromTable);
        $toTable = $this->connection->quoteIdentifier($toTable);
        if (!empty($columns)) {
            $columns = array_map([$this->connection, 'quoteIdentifier'], $columns);
            $selectColumns = implode(',', $columns);
            $duplicateParts = array_map(function ($var) {
                return "$var=VALUES($var)";
            }, $columns);
            $columns = '(' . $selectColumns . ')';
            $onDuplicate = 'ON DUPLICATE KEY UPDATE ' . implode(',', $duplicateParts);
        } else {
            $columns = '';
        }
        if (!empty($conditions)) {
            $conditionParts = [];
            foreach ($conditions as $key => $val) {
                $key = $this->connection->quoteIdentifier($key);
                $val = $this->connection->quote($val);
                $conditionParts[] = "$fromTable.$key=$val";
            }
            $conditions = 'WHERE ' . implode('AND', $conditionParts);
        } else {
            $conditions = '';
        }
        if (!empty($orderBy)) {
            $orderBy = array_map([$this->connection, 'quoteIdentifier'], $orderBy);
            $orderBy = implode(',', $orderBy);
        } else {
            $orderBy = 'NULL';
        }
        return <<<MYSQL
INSERT INTO $toTable $columns
SELECT $selectColumns FROM $fromTable
$conditions
ORDER BY $orderBy $dir
$onDuplicate
MYSQL;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpData($table, array $columns = [], $limit = 1000, $offset = 0)
    {
        $sql = $this->getDumpDataSql($table, $columns);
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $res = $stmt->execute();
        if ($res === false) {
            return false;
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $table
     * @param array $columns
     * @return string
     */
    public function getDumpDataSql($table, array $columns = [])
    {
        $table = $this->connection->quoteIdentifier($table);
        if (!empty($columns)) {
            $columns = array_map([$this->connection, 'quoteIdentifier'], $columns);
            $columns = implode(',', $columns);
        } else {
            $columns = '*';
        }
        return <<<MYSQL
SELECT $columns FROM $table
LIMIT ? OFFSET ?
MYSQL;
    }

    /**
     * {@inheritdoc}
     */
    public function createTmpTable($name, array $columns)
    {
        $sql = $this->getCreateTableSql($name, $columns);
        foreach ($sql as $directive) {
            $this->connection->executeUpdate($directive);
        }
        return true;
    }

    /**
     * @param string $name
     * @param array $columns
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCreateTableSql($name, array $columns)
    {
        $schema = new Schema();
        $table = $schema->createTable($name);
        foreach ($columns as $column => $type) {
            $table->addColumn($column, $type);
        }
        // conditional?
        $table->addOption('temporary', true);
        return $this->connection->getDatabasePlatform()->getCreateTableSQL($table);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        if (is_null($this->driver)) {
            $driver = $this->config['db_driver'];
            if ($driver && $driver instanceof DriverInterface) {
                $this->driver = $driver;
            } else {
                $this->driver = new Driver();
            }
        }
        return $this->driver;
    }
}
