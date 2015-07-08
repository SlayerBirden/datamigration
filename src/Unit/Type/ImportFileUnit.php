<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Action\Exception\WrongContextException;
use Maketok\DataMigration\HashmapInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\AbstractUnit;
use Maketok\DataMigration\Unit\ImportFileUnitInterface;

class ImportFileUnit extends AbstractUnit implements ImportFileUnitInterface
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
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws WrongContextException
     */
    public function getFilesystem()
    {
        if (is_null($this->filesystem)) {
            throw new WrongContextException("Unit does not have filesystem set");
        }
        return $this->filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(ResourceInterface $filesystem)
    {
        $this->filesystem = $filesystem;
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
        return $this;
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
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHashmaps()
    {
        return $this->hashmaps;
    }
}
