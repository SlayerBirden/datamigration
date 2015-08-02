<?php

namespace Maketok\DataMigration\Input;

/**
 * required allow Flat resources to produce structured output
 */
interface ShaperInterface
{
    /**
     * feed string[] row into shaper
     * shaper processes row and decides whether to give output
     * @param array $row
     * @return array|false
     */
    public function feed(array $row);

    /**
     * parse complex entity and return individual rows
     * @param array $entity
     * @return array
     */
    public function parse(array $entity);

    /**
     * clear internal resources
     * @return void
     */
    public function clear();
}
