<?php

namespace Maketok\DataMigration\Input;

interface InputResourceInterface
{
    /**
     * @return array - hashmap of current entity
     */
    public function get();
}
