<?php

namespace Maketok\DataMigration\Storage\Db;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class DBALMysqlResourceInsertNoLoadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DBALMysqlResourceInsertNoLoad
     */
    private $resource;
    /**
     * @var vfsStreamDirectory
     */
    private $root;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    public function setUp()
    {
        $driverMock = $this->getMockBuilder('\Doctrine\DBAL\Driver\PDOMySql\Driver')->getMock();
        $platform = new MySqlPlatform();
        $driverMock->expects($this->any())->method('getDatabasePlatform')->willReturn($platform);
        $con = $this->getMockBuilder('\Doctrine\DBAL\Driver\Connection')->getMock();
        $con->expects($this->any())->method('quote')->willReturnCallback(function ($var) {
            return '\'' . $var . '\'';
        });
        $this->connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->setConstructorArgs([
                ['pdo' => $con],
                $driverMock
            ])
            ->setMethods(['executeUpdate'])
            ->getMock();
        $driverMock = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\DBALMysqlResourceInsertNoLoad')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $refProperty = new \ReflectionProperty(
            '\Maketok\DataMigration\Storage\Db\DBALMysqlResourceInsertNoLoad',
            'connection'
        );
        $refProperty->setAccessible(true);
        $refProperty->setValue($driverMock, $this->connection);

        $this->resource = $driverMock;
        $this->root = vfsStream::setup('root');
    }

    public function testLoad()
    {
        $rows = <<<CSV
data1,2,"buzz bar"
kk2,3,"curly \nhair"

CSV;
        $sql = <<<MYSQL
INSERT INTO `table1`
VALUES (?,?,?)
MYSQL;

        $this->connection->expects($this->exactly(2))->method('executeUpdate')->withConsecutive(
            [
                $this->equalTo($sql), $this->equalTo(['data1', '2', "buzz bar"])
            ],
            [
                $this->equalTo($sql), $this->equalTo(['kk2', '3', "curly \nhair"])
            ]
        );
        $file = vfsStream::newFile('test.csv');
        $file->setContent($rows);
        $file->at($this->root);

        $this->resource->loadData('table1', vfsStream::url('root/test.csv'));
    }
}
