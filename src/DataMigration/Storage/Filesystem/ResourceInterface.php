<?php

namespace Maketok\DataMigration\Storage\Filesystem;

interface ResourceInterface
{
    /**
     * create new file
     * @param string $name
     * @param int $permissions
     * @return bool
     */
    public function newFile($name, $permissions = 0755);

    /**
     * open file
     * @param string $name
     * @param string $mode
     * @return resource
     */
    public function open($name, $mode);

    /**
     * @param resource $handle
     * @param array $row
     * @return bool
     */
    public function writeRow($handle, array $row);

    /**
     * @param resource $handle
     */
    public function close($handle);

    /**
     * @param resource $handle
     * @return array|bool (false)
     */
    public function readRow($handle);
}
