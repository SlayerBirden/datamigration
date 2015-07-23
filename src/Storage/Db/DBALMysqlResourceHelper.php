<?php

namespace Maketok\DataMigration\Storage\Db;

class DBALMysqlResourceHelper implements ResourceHelperInterface
{
    /**
     * @var DBALMysqlResource
     */
    private $resource;

    /**
     * @param DBALMysqlResource $resource
     */
    public function __construct(DBALMysqlResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc
     */
    public function getLastIncrement($table)
    {
        $con = $this->resource->getConnection();
        $builder = $con->createQueryBuilder();
        $builder->select('AUTO_INCREMENT')
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA=?')
            ->andWhere('TABLE_NAME=?');
        $stmt = $con->prepare($builder->getSQL());
        $stmt->bindValue(1, $con->getDatabase());
        $stmt->bindValue(2, $table);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        if ($result !== false && is_array($result)) {
            if (is_null($result[0])) {
                return 1;
            }
            return $result[0];
        }
        throw new \Exception("Could not fetch last increment id.");
    }
}
