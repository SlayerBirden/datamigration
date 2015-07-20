<?php

namespace Maketok\DataMigration\IntegrationTest\Storage\Db;

use Maketok\DataMigration\Action\ConfigInterface;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

class DBALMysqlResourceTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * Returns the test database connection.
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     * @throws \Exception
     */
    protected function getConnection()
    {
        $config = include __DIR__ . '/assets/config.php';
        if (isset($config) && $config instanceof ConfigInterface) {
            $pdo = new \PDO(
                "mysql:dbname={$config['db_name']};host={$config['db_host']}",
                $config['db_user'],
                $config['db_password']
            );
            return $this->createDefaultDBConnection($pdo);
        }
        throw new \Exception("Can't find config file.");
    }

    /**
     * Returns the test dataset.
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/assets/testStructure.xml');
    }

    public function testGetRowCount()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('test1'));
    }
}
