<?php

namespace Maketok\DataMigration\IntegrationTest\Storage\Db;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Db\DBALMysqlResource;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

class DBALMysqlResourceTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var DBALMysqlResource
     */
    private $resource;

    protected function getTearDownOperation()
    {
        return \PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE();
    }

    public function setUp()
    {
        $config = include __DIR__ . '/assets/config.php';
        if (isset($config) && $config instanceof ConfigInterface) {
            $this->config = $config;
            $pdo = new \PDO(
                "mysql:dbname={$config['db_name']};host={$config['db_host']}",
                $config['db_user'],
                $config['db_password']
            );
            $this->config['db_pdo'] = $pdo;
        } else {
            throw new \Exception("Can't find config file.");
        }

        parent::setUp();
        $this->resource = new DBALMysqlResource($this->config);

        // assert that 2 pdo's are same
        $pdo1 = $this->getConnection()->getConnection();
        $ref1 = new \ReflectionProperty(get_class($this->resource->getConnection()), '_conn');
        $ref1->setAccessible(true);
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
        if (isset($this->config['db_pdo'])) {
            return $this->createDefaultDBConnection($this->config['db_pdo']);
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

    public function testGetRowCount()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('customers'));
    }

    public function testCreateTmpTable()
    {
        $this->resource->createTmpTable('tmp_123', ['id' => 'integer']);

        $builder = $this->resource->getConnection()->createQueryBuilder();
        $builder->insert('tmp_123')->values(['id' => 1]);
        $this->resource->getConnection()->executeUpdate($builder->getSQL());

        $this->assertEquals(1, $this->getConnection()->getRowCount('tmp_123'));
    }

    /**
     * @depends testCreateTmpTable
     */
    public function testDelete()
    {
        $this->resource->createTmpTable('tmp_123', ['id' => 'integer']);

        $builder = $this->resource->getConnection()->createQueryBuilder();
        $builder->insert('tmp_123')->values(['id' => 1]);
        $this->resource->getConnection()->executeUpdate($builder->getSQL());

        // now delete
        $res = $this->resource->deleteUsingTempPK('customers', 'tmp_123');

        $this->assertEquals(1, $res);

        // 1 customer left
        $this->assertEquals(1, $this->getConnection()->getRowCount('customers'));
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/expectedCustomersAfterDelete.xml');
        $actual = $this->getConnection()->createQueryTable("customers", "SELECT * FROM customers");
        $this->assertTablesEqual($expected->getTable('customers'), $actual);
    }
}
