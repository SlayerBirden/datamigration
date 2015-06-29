<?php

namespace Maketok\DataMigration\Unit;

abstract class AbstractUnit implements UnitInterface
{
    /**
     * @var string
     */
    protected $tableName;
    /**
     * @var string|callable
     */
    protected $isEntityCondition;
    /**
     * @var array|string[]|callable[]
     */
    protected $contributions = [];
    /**
     * @var array|string[]|callable[]
     */
    protected $writeConditions = [];
    /**
     * @var string[]
     */
    protected $mapping;

    /**
     * @var string
     */
    protected $tmpTable;
    /**
     * @var string
     */
    protected $tmpFileName;

    /**
     * {@inheritdoc}
     */
    public function getTmpTable()
    {
        return $this->tmpTable;
    }

    /**
     * @return string[]
     */
    public function getMapping()
    {
        return $this->mapping;
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
    public function getTmpFileName()
    {
        return $this->tmpFileName;
    }

    /**
     * {@inheritdoc}
     */
    public function setTmpFileName($tmpFileName)
    {
        $this->tmpFileName = $tmpFileName;
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
    public function setIsEntityCondition($condition)
    {
        $this->isEntityCondition = $condition;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addContribution($contribution)
    {
        $this->contributions[] = $contribution;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addWriteCondition($condition)
    {
        $this->writeConditions[] = $condition;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
        return $this;
    }

    /**
     * @return callable|string
     */
    public function getIsEntityCondition()
    {
        return $this->isEntityCondition;
    }

    /**
     * @return array|callable[]|string[]
     */
    public function getContributions()
    {
        return $this->contributions;
    }

    /**
     * @return array|callable[]|string[]
     */
    public function getWriteConditions()
    {
        return $this->writeConditions;
    }
}
