<?php

namespace Maketok\DataMigration\Unit;

use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;

abstract class AbstractUnit implements UnitInterface
{
    /**
     * @var string
     */
    protected $code;
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
     * Order by columns for reverse move
     * @var array
     */
    protected $reverseMoveOrder;
    /**
     * Directions for reverse move
     * @var array
     */
    protected $reverseMoveDirections;
    /**
     * Conditions for reverse move
     * @var array
     */
    protected $reverseMoveConditions;
    /**
     * Max number with and center of dispersion
     * @var array|int[]
     */
    protected $generationSeed;
    /**
     * @var ResourceInterface
     */
    private $filesystem;

    /**
     * @param $code
     * @param null $tableName
     * @param ResourceInterface $filesystem
     * @param array $mapping
     * @param string $isEntityCondition
     * @param array $validationRules
     * @param array $writeConditions
     * @param array $contributions
     * @param array $reversedMapping
     * @param array $reverseMoveOrder
     * @param array $reverseMoveDirections
     * @param array $reverseMoveConditions
     * @param array $generatorMapping
     * @param array $generationSeed
     */
    public function __construct(
        $code,
        $tableName = null,
        ResourceInterface $filesystem = null,
        array $mapping = [],
        $isEntityCondition = "",
        $validationRules = [],
        $writeConditions = [],
        $contributions = [],
        array $reversedMapping = [],
        array $reverseMoveOrder = [],
        array $reverseMoveDirections = [],
        array $reverseMoveConditions = [],
        array $generatorMapping = [],
        array $generationSeed = [1, 1]
    ) {
        $this->code = $code;
        $this->tableName = $tableName;
        $this->mapping = $mapping;
        $this->isEntityCondition = $isEntityCondition;
        $this->validationRules = $validationRules;
        $this->writeConditions = $writeConditions;
        $this->contributions = $contributions;
        $this->reversedMapping = $reversedMapping;
        $this->reverseMoveOrder = $reverseMoveOrder;
        $this->reverseMoveDirections = $reverseMoveDirections;
        $this->reverseMoveConditions = $reverseMoveConditions;
        $this->generatorMapping = $generatorMapping;
        $this->generationSeed = $generationSeed;
        $this->filesystem = $filesystem;
    }

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

    /**
     * @return array
     */
    public function getReverseMoveOrder()
    {
        return $this->reverseMoveOrder;
    }

    /**
     * @param array $reverseMoveOrder
     * @return $this
     */
    public function setReverseMoveOrder(array $reverseMoveOrder)
    {
        $this->reverseMoveOrder = $reverseMoveOrder;
        return $this;
    }

    /**
     * @return array
     */
    public function getReverseMoveDirections()
    {
        return $this->reverseMoveDirections;
    }

    /**
     * @param array $reverseMoveDirections
     * @return $this
     */
    public function setReverseMoveDirections(array $reverseMoveDirections)
    {
        $this->reverseMoveDirections = $reverseMoveDirections;
        return $this;
    }

    /**
     * @return array
     */
    public function getReverseMoveConditions()
    {
        return $this->reverseMoveConditions;
    }

    /**
     * @param array $reverseMoveConditions
     * @return $this
     */
    public function setReverseMoveConditions($reverseMoveConditions)
    {
        $this->reverseMoveConditions = $reverseMoveConditions;
        return $this;
    }

    /**
     * get Number of possible occurrences of the
     * @return array
     */
    public function getGenerationSeed()
    {
        return $this->generationSeed;
    }

    /**
     * @param array $generationSeed
     * @return $this
     */
    public function setGenerationSeed(array $generationSeed)
    {
        $this->generationSeed = $generationSeed;
        return $this;
    }

    /**
     * @return ResourceInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param ResourceInterface $filesystem
     * @return $this
     */
    public function setFilesystem($filesystem)
    {
        $this->filesystem = $filesystem;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function __destruct()
    {
        if (isset($this->filesystem) && $this->filesystem->isActive()) {
            @$this->filesystem->close();
        }
    }
}
