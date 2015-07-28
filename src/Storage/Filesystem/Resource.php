<?php

namespace Maketok\DataMigration\Storage\Filesystem;

class Resource implements ResourceInterface
{
    /**
     * @var \SplFileObject
     */
    private $descriptor;

    /**
     * {@inheritdoc}
     */
    public function open($name, $mode)
    {
        if (!file_exists(dirname($name))) {
            mkdir(dirname($name), 0755, true);
        }
        $this->descriptor = new \SplFileObject($name, $mode);
        $this->descriptor->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRow(array $row, $delimiter = ',', $enclosure = '"')
    {
        $row = array_map(function ($var) {
            if (is_null($var)) {
                return '\N';
            }
            return $var;
        }, $row);
        return $this->descriptor->fputcsv($row, $delimiter, $enclosure);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->descriptor = null;
    }

    /**
     * {@inheritdoc}
     */
    public function readRow($delimiter = ',', $enclosure = '"', $escape = "\\")
    {
        return $this->descriptor->fgetcsv($delimiter, $enclosure, $escape);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->descriptor->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return isset($this->descriptor);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp($filename)
    {
        $dir = dirname($filename);
        unlink($filename);
        if ($this->isEmptyDir($dir)) {
            rmdir($dir);
        }
    }

    /**
     * @param $directory
     * @return bool|null
     */
    public function isEmptyDir($directory)
    {
        if (!file_exists($directory) || !is_dir($directory) || !is_readable($directory)) {
            return null;
        }
        return count(scandir($directory)) == 2;
    }
}
