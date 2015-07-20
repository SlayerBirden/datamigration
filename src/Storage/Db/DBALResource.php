<?php

namespace Maketok\DataMigration\Storage\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Maketok\DataMigration\Action\ConfigInterface;

class DBALResource implements ResourceInterface
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->open();
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->connection = new Connection([
            'dbname' => $this->config['db_name'],
            'user' => $this->config['db_user'],
            'password' => $this->config['db_password'],
            'host' => $this->config['db_host'],
        ], $this->config['db_driver']);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * GC opened resource
     */
    public function __destruct()
    {
        if ($this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUsingTempPK($deleteTable, $tmpTable, $primaryKey = 'id')
    {
        $deleteTable = $this->connection->quoteIdentifier($deleteTable);
        $tmpTable = $this->connection->quoteIdentifier($tmpTable);
        if (!is_array($primaryKey)) {
            $primaryKey = [$primaryKey];
        }
        $primaryKey = array_map([$this->connection, 'quoteIdentifier'], $primaryKey);
        $conditionParts = [];
        foreach ($primaryKey as $key) {
            $conditionParts[] = "main_table.$key = tmp_table.$key";
        }
        $condition = implode('AND', $conditionParts);
        $sql = <<<MYSQL
DELETE main_table FROM $deleteTable AS main_table
JOIN $tmpTable AS tmp_table ON $condition;
MYSQL;
        return $this->connection->executeUpdate($sql);
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
        $sql = <<<MYSQL
LOAD DATA $localKey INFILE '$file'
INTO TABLE `$table`
FIELDS
    TERMINATED BY '$delimiter'
    $optionalKey ENCLOSED BY '$enclosure'
    ESCAPED BY '$escape'
LINES
    TERMINATED BY '$termination'
$columns
$set
MYSQL;
        return $this->connection->executeUpdate($sql);
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
                $val = $this->connection->quote($val);
                $conditionParts = "$key=$val";
            }
            $conditions = 'WHERE ' . implode('AND', $conditionParts);
        } else {
            $conditions = '';
        }
        if (!empty($orderBy)) {
            $orderBy = implode(',', $orderBy);
        } else {
            $orderBy = 'NULL';
        }
        $sql = <<<MYSQL
INSERT INTO `$toTable` $columns
SELECT $selectColumns FROM `$fromTable`
$conditions
ORDER BY $orderBy $dir
$onDuplicate
MYSQL;
        return $this->connection->executeUpdate($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpData($table, array $columns = null, $limit = 1000, $offset = 0)
    {
        $table = $this->connection->quoteIdentifier($table);
        if (!empty($columns)) {
            $columns = array_map([$this->connection, 'quoteIdentifier'], $columns);
            $columns = implode(',', $columns);
        } else {
            $columns = '*';
        }
        $sql = <<<MYSQL
SELECT $columns FROM $table
LIMIT ? OFFSET ?
MYSQL;
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
     * {@inheritdoc}
     */
    public function createTmpTable($name, array $columns)
    {
        $schema = new Schema();
        $table = $schema->createTable($name);
        foreach ($columns as $column) {
            $table->addColumn($column, 'text');
        }
        $sql = $this->connection->getDatabasePlatform()->getCreateTableSQL($table);
        // TODO convert to tmp?
        $res = $this->connection->executeUpdate($sql);
        return $res > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }
}
