<?php

namespace Maketok\DataMigration\Storage\Db;

interface ResourceHelperInterface
{
    /**
     * Get last increment value for given table
     * @param string $table
     * @return mixed
     */
    public function getLastIncrement($table);
}
