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
            $this->resource = new DBALMysqlResource($this->config);
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

    public function testGetRowCount()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('customers'));
    }

    public function testCreateTmpTable()
    {
        $this->resource->createTmpTable('tmp_123', [
            'id' => 'integer',
            'testnull' => ['text', ['notnull' => false]]
        ]);

        $builder = $this->resource->getConnection()->createQueryBuilder();
        $builder->insert('tmp_123')->values(['id' => 1]);
        $this->resource->getConnection()->executeUpdate($builder->getSQL());

        $this->assertEquals(1, $this->getConnection()->getRowCount('tmp_123'));
        // assert table
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/tmp_123.xml');
        $actual = $this->getConnection()->createQueryTable("tmp_123", "SELECT * FROM `tmp_123`");
        $this->assertTablesEqual($expected->getTable('tmp_123'), $actual);
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
        // assert table
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/expectedCustomersAfterDelete.xml');
        $actual = $this->getConnection()->createQueryTable("customers", "SELECT * FROM `customers`");
        $this->assertTablesEqual($expected->getTable('customers'), $actual);
    }

    public function testLoad()
    {
        $file = __DIR__ . '/assets/toLoad.csv';
        $this->resource->loadData('customers', $file, true);

        $this->assertEquals(4, $this->getConnection()->getRowCount('customers'));
        // assert table
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/expectedCustomersAfterLoad.xml');
        $actual = $this->getConnection()->createQueryTable("customers", "SELECT * FROM `customers`");
        $this->assertTablesEqual($expected->getTable('customers'), $actual);
    }

    /**
     * @depends testCreateTmpTable
     */
    public function testMove1()
    {
        $this->resource->createTmpTable('tmp_123', ['id' => 'integer', 'firstname' => 'string']);

        $this->resource->move('customers', 'tmp_123', ['id', 'firstname'], [], ['id'], 'DESC');

        $this->assertEquals(2, $this->getConnection()->getRowCount('tmp_123'));
        // assert table
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/expectedCustomersAfterMove1.xml');
        $actual = $this->getConnection()->createQueryTable("tmp_123", "SELECT * FROM `tmp_123`");
        $this->assertTablesEqual($expected->getTable('tmp_123'), $actual);
    }

    /**
     * @depends testCreateTmpTable
     */
    public function testMove2()
    {
        $this->resource->createTmpTable('tmp_123', ['id' => 'integer', 'firstname' => 'string']);

        $this->resource->move(
            'customers',
            'tmp_123',
            ['id', 'firstname'],
            ['firstname' => [
                'nin' => ['Bob']
            ]]
        );

        $this->assertEquals(1, $this->getConnection()->getRowCount('tmp_123'));
        // assert table
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/expectedCustomersAfterMove2.xml');
        $actual = $this->getConnection()->createQueryTable("tmp_123", "SELECT * FROM `tmp_123`");
        $this->assertTablesEqual($expected->getTable('tmp_123'), $actual);
    }

    public function testDump()
    {
        $expected1 = array(
            array('id' => '1', 'firstname' => 'Oleg'),
        );
        $this->assertSame($expected1, $this->resource->dumpData('customers', ['id', 'firstname'], 1));
        $expected2 = array(
            array('id' => '2', 'firstname' => 'Bob', 'lastname' => 'Bobbington',
                'age' => '35', 'email' => 'bb@example.com')
        );
        $this->assertSame($expected2, $this->resource->dumpData('customers', [], 1, 1));
    }
}
