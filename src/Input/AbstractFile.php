<?php

namespace Maketok\DataMigration\Input;

abstract class AbstractFile implements InputResourceInterface
{
    /**
     * @var \SplFileObject
     */
    protected $descriptor;

    /**
     * @param string $fileName
     * @param string $mode
     */
    public function __construct($fileName, $mode = 'r')
    {
        $this->descriptor = new \SplFileObject($fileName, $mode);
    }

    /**
     * close file resource
     */
    public function close()
    {
        if (isset($this->descriptor)) {
            $this->descriptor = null;
        }
    }

    /**
     * clean internal pointer
     */
    public function __destruct()
    {
        $this->close();
    }
}
