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
    protected $entityCondition;
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
    public function setIsEntityCondition($condition)
    {
        $this->entityCondition = $condition;
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
}
