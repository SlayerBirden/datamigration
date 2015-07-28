<?php

namespace Maketok\DataMigration\IntegrationTest\Storage\Db;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Db\DBALMysqlResource;
use Maketok\DataMigration\Storage\Db\DBALMysqlResourceInsertNoLoad;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

class DBALMysqlResourceInsertNoLoadTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var DBALMysqlResource
     */
    private $resource;
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * {@inheritdoc}
     */
    protected function getTearDownOperation()
    {
        return \PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $config = include __DIR__ . '/assets/config.php';
        if (isset($config) && $config instanceof ConfigInterface) {
            $this->config = $config;
            $this->resource = new DBALMysqlResourceInsertNoLoad($this->config);
            $ref1 = new \ReflectionProperty(get_class($this->resource->getConnection()), '_conn');
            $ref1->setAccessible(true);
            $this->pdo = $ref1->getValue($this->resource->getConnection());
        } else {
            throw new \Exception("Can't find config file.");
        }

        parent::setUp();

        // assert that 2 pdo's are same
        $pdo1 = $this->getConnection()->getConnection();
        $pdo2 = $ref1->getValue($this->resource->getConnection());

        $this->assertSame($pdo1, $pdo2);
    }

    /**
     * Returns the test database connection.
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     * @throws \Exception
     */
    protected function getConnection()
    {
        if (isset($this->pdo)) {
            return $this->createDefaultDBConnection($this->pdo);
        }
        throw new \Exception("Can't find pdo in config.");
    }

    /**
     * Returns the test dataset.
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/assets/testStructure.xml');
    }

    public function testLoad()
    {
        $file = __DIR__ . '/assets/toLoad.csv';
        $this->resource->loadData('customers', $file, true);

        $this->assertEquals(4, $this->getConnection()->getRowCount('customers'));
        // assert table
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/expectedCustomersAfterLoadInserted.xml');
        $actual = $this->getConnection()->createQueryTable("customers", "SELECT * FROM `customers`");
        $this->assertTablesEqual($expected->getTable('customers'), $actual);
    }
}
