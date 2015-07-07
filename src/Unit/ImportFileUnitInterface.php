<?php

namespace Maketok\DataMigration\Unit;

use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;

interface ImportFileUnitInterface extends UnitInterface
{
    /**
     * set condition that would determine if current row is Entity for current unit
     * @param string|callable $condition
     * @return self
     */
    public function setIsEntityCondition($condition);

    /**
     * Get condition to check if current row is entity
     * @return string|callable $condition
     */
    public function getIsEntityCondition();

    /**
     * add directive that should be executed for each row
     * @param string|callable $contribution
     * @return self
     */
    public function addContribution($contribution);

    /**
     * get contributions
     * @return string[]|callable[]
     */
    public function getContributions();

    /**
     * add condition based on which it would be clear if we should add current entity
     * @param string|callable $condition
     * @return self
     */
    public function addWriteCondition($condition);

    /**
     * get write conditions
     * @return string[]|callable[]
     */
    public function getWriteConditions();

    /**
     * set current mapping
     * @param array|string[] $mapping hashmap
     * @return self
     */
    public function setMapping(array $mapping);

    /**
     * get current mapping
     * @return array|string[]
     */
    public function getMapping();

    /**
     * set Temporary file name generated for this unit
     * @param string $name
     * @return mixed
     */
    public function setTmpFileName($name);

    /**
     * get tmp file name
     * @return string
     */
    public function getTmpFileName();

    /**
     * add condition based on which it would be clear if we should add current entity
     * @param string|callable $condition
     * @return self
     */
    public function addValidationRule($condition);

    /**
     * get validation conditions
     * @return string[]|callable[]
     */
    public function getValidationRules();

    /**
     * @return ResourceInterface
     */
    public function getFilesystem();

    /**
     * set IO handler
     * @param ResourceInterface $filesystem
     * @return self
     */
    public function setFilesystem(ResourceInterface $filesystem);
}
