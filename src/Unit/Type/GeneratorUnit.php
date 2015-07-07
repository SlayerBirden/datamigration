<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\GenerateUnitInterface;

class GeneratorUnit extends ImportFileUnit implements GenerateUnitInterface
{
    /**
     * @var \SplFixedArray
     */
    protected $generatorMapping = [];
    /**
     * Max number with and center of dispersion
     * @var \SplFixedArray
     */
    protected $generationSeed;

    /**
     * @param string $code
     * @param \SplFixedArray $generationSeed
     */
    public function __construct($code, \SplFixedArray $generationSeed = null)
    {
        $this->code = $code;
        if (is_null($generationSeed)) {
            $this->generationSeed = new \SplFixedArray(2);
            $this->generationSeed[0] = 1;
            $this->generationSeed[1] = 1;
        }
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
        return $this;
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
        return $this;
    }
}
