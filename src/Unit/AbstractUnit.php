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
     * @var array|string[]|callable[]
     */
    protected $validationRules = [];
    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var array
     */
    protected $generatorMapping;

    /**
     * @var array
     */
    protected $reversedMapping;

    /**
     * @var string
     */
    protected $tmpTable;
    /**
     * @var string
     */
    protected $tmpFileName;
    /**
     * @var string|string[]
     */
    protected $pk;

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

    /**
     * {@inheritdoc}
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * {@inheritdoc}
     */
    public function setPk($pk)
    {
        $this->pk = $pk;
        return $this;
    }

    /**
     * @return array
     */
    public function getGeneratorMapping()
    {
        return $this->generatorMapping;
    }

    /**
     * @param array $generatorMapping
     * @return $this
     */
    public function setGeneratorMapping($generatorMapping)
    {
        $this->generatorMapping = $generatorMapping;
        return $this;
    }

    /**
     * @return array
     */
    public function getReversedMapping()
    {
        return $this->reversedMapping;
    }

    /**
     * @param array $reversedMapping
     * @return $this
     */
    public function setReversedMapping($reversedMapping)
    {
        $this->reversedMapping = $reversedMapping;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addValidationRule($condition)
    {
        $this->validationRules[] = $condition;
        return $this;
    }

    /**
     * @return array|callable[]|string[]
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }
}
