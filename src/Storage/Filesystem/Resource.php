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
        $this->descriptor = new \SplFileObject($name, $mode);
        $this->descriptor->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRow(array $row, $delimiter = ',', $enclosure = '"')
    {
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
}
