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
     * @return self
     */
    public function add(array $entity);

    /**
     * reset internal counter
     * @return mixed
     */
    public function reset();
}
