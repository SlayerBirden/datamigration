<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\ImportDbUnitInterface;

class ImportDbUnit extends ImportFileUnit implements ImportDbUnitInterface
{
    /**
     * @var string
     */
    protected $tableName;
    /**
     * @var string
     */
    protected $tmpTable;
    /**
     * @var string|string[]
     */
    protected $primaryKey;

    /**
     * {@inheritdoc}
     */
    public function getTmpTable()
    {
        return $this->tmpTable;
    }

    /**
     * {@inheritdoc}
     */
    public function setTmpTable($tmpTable)
    {
        $this->tmpTable = $tmpTable;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTable($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getPk()
    {
        return $this->primaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setPk($pk)
    {
        $this->primaryKey = $pk;
        return $this;
    }
}
