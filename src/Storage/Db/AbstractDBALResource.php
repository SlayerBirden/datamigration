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
            'port' => $this->config['db_port'],
        ];
        if (isset($this->config['db_pdo'])) {
            $params['pdo'] = $this->config['db_pdo'];
        }
        if (isset($this->config['db_url'])) {
            $params['url'] = $this->config['db_url'];
        }
        $params['driverOptions'] = $this->getDriverOptions();
        $this->connection = new Connection($params, $this->getDriver());
        return $this->connection->connect();
    }

    /**
     * @return array
     */
    protected function getDriverOptions()
    {
        return [];
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

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
