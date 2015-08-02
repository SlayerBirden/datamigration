<?php

namespace Maketok\DataMigration\Input;

interface InputResourceInterface
{
    /**
     * @return array|bool (false) - hashmap of current entity
     */
    public function get();

    /**
     * add entity to input resource
     * @param array $entity
     * @return void
     */
    public function add(array $entity);

    /**
     * reset internal counter
     * @return void
     */
    public function reset();
}
