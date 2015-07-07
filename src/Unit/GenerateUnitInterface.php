<?php

namespace Maketok\DataMigration\Unit;

interface GenerateUnitInterface extends ImportFileUnitInterface
{
    /**
     * @return array
     */
    public function getGeneratorMapping();

    /**
     * @param array $generatorMapping
     * @return $this
     */
    public function setGeneratorMapping(array $generatorMapping);

    /**
     * get Number of possible occurrences
     * must go in form of array with 1st el is max, second is center
     * @return \SplFixedArray
     */
    public function getGenerationSeed();

    /**
     * @param \SplFixedArray $generationSeed
     * @return $this
     */
    public function setGenerationSeed(\SplFixedArray $generationSeed);
}
