<?php

namespace Maketok\DataMigration\Storage\Db;

use Maketok\DataMigration\Action\ConfigInterface;

class PdoResource implements ResourceInterface
{
    /**
     * @var \PDO
     */
    private $pdo;
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
        $this->pdo = new \PDO(
            $this->config['db_dns'],
            $this->config['db_username'],
            $this->config['db_password'],
            $this->config['db_options']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->pdo = null;
    }

    /**
     * GC opened resource
     */
    public function __destruct()
    {
        if (!is_null($this->pdo)) {
            $this->pdo = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUsingTempPK($deleteTable, $tmpTable, $primaryKey = 'id')
    {
        $sql = <<<MYSQL
DELETE main_table FROM $deleteTable AS main_table
JOIN $tmpTable AS tmp_table ON main_table.$primaryKey = tmp_table.$primaryKey;
MYSQL;
        $stmt = $this->pdo->prepare($sql);
        return $this->pdo->exec($stmt);
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
    )
    {
        $localKey = $local ? 'LOCAL' : '';
        $optionalKey = $optionallyEnclosed ? 'OPTIONALLY' : '';
        if ($columns) {
            $columns = '(' . implode(',', $columns) . ')';
        } else {
            $columns = '';
        }
        if ($set) {
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
        $stmt = $this->pdo->prepare($sql);
        return $this->pdo->exec($stmt);
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
    )
    {
        $selectColumns = '*';
        $onDuplicate = '';
        if ($columns) {
            $selectColumns = implode(',', $columns);
            $duplicateParts = array_map(function ($var) {
                return "$var=VALUES($var)";
            }, $columns);
            $columns = '(' . $selectColumns . ')';
            $onDuplicate = 'ON DUPLICATE KEY UPDATE ' . implode(',', $duplicateParts);
        } else {
            $columns = '';
        }
        if ($conditions) {
            $conditionParts = [];
            foreach ($conditions as $key => $val) {
                $conditionParts = "$key=$val";
            }
            $conditions = 'WHERE ' . implode('AND', $conditionParts);
        } else {
            $conditions = '';
        }
        if ($orderBy) {
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
        $stmt = $this->pdo->prepare($sql);
        return $this->pdo->exec($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpData($table, array $columns = null, $limit = 1000, $offset = 0)
    {
        // TODO: Implement dumpData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createTmpTable($name, array $columns)
    {
        // TODO: Implement createTmpTable() method.
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        $this->pdo->rollBack();
    }
}
