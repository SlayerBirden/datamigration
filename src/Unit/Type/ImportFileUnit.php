<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\HashmapInterface;
use Maketok\DataMigration\Storage\Filesystem\Resource;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;

abstract class ImportFileUnit extends AbstractUnit implements ImportFileUnitInterface
{
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
    protected $mapping = [];
    /**
     * @var string
     */
    protected $tmpFileName;
    /**
     * @var ResourceInterface
     */
    protected $filesystem;
    /**
     * @var HashmapInterface[]
     */
    protected $hashmaps;

    public function __construct($code)
    {
        parent::__construct($code);
        $this->filesystem = new Resource();
    }

    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsEntityCondition()
    {
        return $this->isEntityCondition;
    }

    /**
     * {@inheritdoc}
     */
    public function setIsEntityCondition($condition)
    {
        $this->isEntityCondition = $condition;
    }

    /**
     * {@inheritdoc
     */
    public function getContributions()
    {
        return $this->contributions;
    }

    /**
     * {@inheritdoc}
     */
    public function addContribution($contribution)
    {
        $this->contributions[] = $contribution;
    }

    /**
     * {@inheritdoc}
     */
    public function getWriteConditions()
    {
        return $this->writeConditions;
    }

    /**
     * {@inheritdoc}
     */
    public function addWriteCondition($condition)
    {
        $this->writeConditions[] = $condition;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(ResourceInterface $filesystem)
    {
        $this->filesystem = $filesystem;
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
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * {@inheritdoc}
     */
    public function addValidationRule($condition)
    {
        $this->validationRules[] = $condition;
    }

    /**
     * close IO if it was not closed
     */
    public function __destruct()
    {
        if (isset($this->filesystem) && $this->filesystem->isActive()) {
            $this->filesystem->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addHashmap(HashmapInterface $hashmap)
    {
        $this->hashmaps[$hashmap->getCode()] = $hashmap;
    }

    /**
     * {@inheritdoc}
     */
    public function getHashmaps()
    {
        return $this->hashmaps;
    }
}
