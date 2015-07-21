<?php

namespace Maketok\DataMigration\Storage\Db;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Maketok\DataMigration\Action\ArrayConfig;

class DBALMysqlResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DBALMysqlResource
     */
    private $resource;
    /**
     * @var ArrayConfig
     */
    private $config;

    public function setUp()
    {
        $this->config = new ArrayConfig();
        $driverMock = $this->getMockBuilder('\Doctrine\DBAL\Driver\PDOMySql\Driver')->getMock();
        $platform = new MySqlPlatform();
        $driverMock->expects($this->any())->method('getDatabasePlatform')->willReturn($platform);
        $this->config['db_driver'] = $driverMock;
        $con = $this->getMockBuilder('\Doctrine\DBAL\Driver\Connection')->getMock();
        $con->expects($this->any())->method('quote')->willReturnCallback(function ($var) {
            return '\'' . $var . '\'';
        });
        $this->config['db_pdo'] = $con;
        $this->resource = new DBALMysqlResource($this->config);
    }

    public function testGetDeleteSql()
    {
        $sql = $this->resource->getDeleteUsingTempPkSql('test_table1', 'tmp_test_table1', ['id']);
        $expected = <<<MYSQL
DELETE main_table FROM `test_table1` AS main_table
JOIN `tmp_test_table1` AS tmp_table ON `main_table`.`id`=`tmp_table`.`id`
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetLoadSql()
    {
        $sql = $this->resource->getLoadDataSql(
            'test_table1', '/tmp/file1', true, ['name', 'code'], ['name' => 'code', 'code' => 'name'],
            ",", '"', "\\", "\n", true);
        $expected = <<<MYSQL
LOAD DATA LOCAL INFILE '/tmp/file1'
INTO TABLE `test_table1`
FIELDS
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
    ESCAPED BY '\\'
LINES
    TERMINATED BY '\n'
(name,code)
SET name=code,code=name
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetMoveSql()
    {
        $sql = $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => 1], ['name']);
        $expected = <<<MYSQL
INSERT INTO `table1` (`id`,`name`)
SELECT `id`,`name` FROM `tmp_table1`
WHERE `tmp_table1`.`id`='1'
ORDER BY `name` ASC
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`),`name`=VALUES(`name`)
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetMoveSql2()
    {
        $sql = $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['eq' => 1]], ['name']);
        $expected = <<<MYSQL
INSERT INTO `table1` (`id`,`name`)
SELECT `id`,`name` FROM `tmp_table1`
WHERE `tmp_table1`.`id`='1'
ORDER BY `name` ASC
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`),`name`=VALUES(`name`)
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetMoveSql3()
    {
        $sql = $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['neq' => 1]], ['name']);
        $expected = <<<MYSQL
INSERT INTO `table1` (`id`,`name`)
SELECT `id`,`name` FROM `tmp_table1`
WHERE `tmp_table1`.`id`<>'1'
ORDER BY `name` ASC
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`),`name`=VALUES(`name`)
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetMoveSql4()
    {
        $sql = $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['in' => [1]]], ['name']);
        $expected = <<<MYSQL
INSERT INTO `table1` (`id`,`name`)
SELECT `id`,`name` FROM `tmp_table1`
WHERE `tmp_table1`.`id` in ('1')
ORDER BY `name` ASC
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`),`name`=VALUES(`name`)
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetMoveSql5()
    {
        $sql = $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['nin' => [1]]], ['name']);
        $expected = <<<MYSQL
INSERT INTO `table1` (`id`,`name`)
SELECT `id`,`name` FROM `tmp_table1`
WHERE `tmp_table1`.`id` not in ('1')
ORDER BY `name` ASC
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`),`name`=VALUES(`name`)
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    /**
     * @expectedException \Maketok\DataMigration\Storage\Exception\ParsingException
     * @expectedExceptionMessage Condition should contain only 1 element
     */
    public function testGetWrongMoveSql1()
    {
        $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['eq',2]], ['name']);
    }

    /**
     * @expectedException \Maketok\DataMigration\Storage\Exception\ParsingException
     * @expectedExceptionMessage Could not resolve condition operation wrong
     */
    public function testGetWrongMoveSql2()
    {
        $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['wrong' => 2]], ['name']);
    }

    /**
     * @expectedException \Maketok\DataMigration\Storage\Exception\ParsingException
     * @expectedExceptionMessage Can not use 'in' operation with non array
     */
    public function testGetWrongMoveSql3()
    {
        $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['in' => 2]], ['name']);
    }

    /**
     * @expectedException \Maketok\DataMigration\Storage\Exception\ParsingException
     * @expectedExceptionMessage Can not use 'nin' operation with non array
     */
    public function testGetWrongMoveSql4()
    {
        $this->resource->getMoveSql('tmp_table1', 'table1', ['id', 'name'], ['id' => ['nin' => 2]], ['name']);
    }

    public function testGetDumpSql()
    {
        $sql = $this->resource->getDumpDataSql('tmp_table1', ['id', 'name']);
        $expected = <<<MYSQL
SELECT `id`,`name` FROM `tmp_table1`
LIMIT ? OFFSET ?
MYSQL;
        $this->assertEquals($expected, $sql);
    }

    public function testGetCreateTableSql()
    {
        $columns = ['id' => 'integer', 'name' => 'string'];
        $sql = $this->resource->getCreateTableSql('tmp_table1', $columns);

        $platform = new MySqlPlatform();
        $schema = new Schema();
        $table = $schema->createTable('tmp_table1');
        foreach ($columns as $column => $type) {
            $table->addColumn($column, $type);
        }
        $table->addOption('temporary', true);
        $expected = $platform->getCreateTableSQL($table);

        $this->assertEquals($expected, $sql);
    }
}
