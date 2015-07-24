<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\GenerateUnitInterface;

abstract class GeneratorUnit extends ExportFileUnit implements GenerateUnitInterface
{
    /**
     * @var array
     */
    protected $generatorMapping;
    /**
     * Max number and center of dispersion
     * @var \SplFixedArray
     */
    protected $generationSeed;

    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct($code);
        $this->generationSeed = new \SplFixedArray(2);
        $this->generationSeed[0] = 1;
        $this->generationSeed[1] = 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getGeneratorMapping()
    {
        return $this->generatorMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function setGeneratorMapping(array $generatorMapping)
    {
        $this->generatorMapping = $generatorMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerationSeed()
    {
        return $this->generationSeed;
    }

    /**
     * {@inheritdoc}
     */
    public function setGenerationSeed(\SplFixedArray $generationSeed)
    {
        $this->generationSeed = $generationSeed;
    }
}
