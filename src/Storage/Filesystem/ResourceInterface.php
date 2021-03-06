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
     * @return int|bool length of written string or false on failure
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
     * return pointer to start of the file
     * @return void
     */
    public function rewind();

    /**
     * check if resource is opened
     * @return bool
     */
    public function isActive();

    /**
     * delete the filename
     * also attempts to delete the directory the file's in if that's empty
     * @param string $filename
     * @return void
     */
    public function cleanUp($filename);
}
