<?php

namespace Maketok\DataMigration\Unit;

interface GenerateUnitInterface extends UnitInterface
{
    /**
     * @return array
     */
    public function getGeneratorMapping();

    /**
     * @param array $generatorMapping
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
     */
    public function setGenerationSeed(\SplFixedArray $generationSeed);

    /**
     * get contributions for generation process
     * @return array
     */
    public function getGenerationContributions();

    /**
     * set generation contributions
     * @param array $generationContributions
     * @return void
     */
    public function setGenerationContributions(array $generationContributions);
}
