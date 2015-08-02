<?php

namespace Maketok\DataMigration\Input;

class ArrayInput implements InputResourceInterface, \Iterator
{
    /**
     * @var array
     */
    private $entries = [];
    /**
     * @var int
     */
    private $index = 0;

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (!$this->valid()) {
            return false;
        }
        $current = $this->current();
        $this->next();
        return $current;
    }

    /**
     * {@inheritdoc}
     */
    public function add(array $entity)
    {
        $this->entries[] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->entries[$this->index];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->index += 1;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->entries[$this->index]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->index = 0;
    }
}
