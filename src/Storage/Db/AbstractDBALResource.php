<?php

namespace Maketok\DataMigration\Storage\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver as DriverInterface;
use Maketok\DataMigration\Action\ConfigInterface;

abstract class AbstractDBALResource implements ResourceInterface
{
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var ConfigInterface
     */
    protected $config;

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
        $params = [
            'dbname' => $this->config['db_name'],
            'user' => $this->config['db_user'],
            'password' => $this->config['db_password'],
            'host' => $this->config['db_host'],
        ];
        if (isset($this->config['db_pdo'])) {
            $params['pdo'] = $this->config['db_pdo'];
            unset($this->config['db_pdo']);
        }
        $this->connection = new Connection($params, $this->getDriver());
        return $this->connection->connect();
    }

    /**
     * @return DriverInterface
     */
    abstract protected function getDriver();

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
