<?php

namespace Maketok\DataMigration\Storage\Filesystem;

interface ResourceInterface
{
    /**
     * open file
     * @param string $name
     * @param string $mode
     * @return bool
     */
    public function open($name, $mode);

    /**
     * @param array $row
     * @return bool
     */
    public function writeRow(array $row);

    /**
     * close connection
     */
    public function close();

    /**
     * @return array|bool (false)
     */
    public function readRow();

    /**
     * check if resource is opened
     * @return bool
     */
    public function isActive();
}
