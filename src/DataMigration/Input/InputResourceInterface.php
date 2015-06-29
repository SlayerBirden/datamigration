<?php

namespace Maketok\DataMigration\Input;

interface InputResourceInterface
{
    /**
     * @return array - hashmap of current entity
     */
    public function get();

    /**
     * add row to input resource
     * @param array $entity
     * @return mixed
     */
    public function add(array $entity);

    /**
     * create input resource based on current set of entities
     * @return mixed
     */
    public function assemble();

    /**
     * reset internal counter
     * @return mixed
     */
    public function reset();
}
