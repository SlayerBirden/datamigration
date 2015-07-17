<?php

namespace Maketok\DataMigration\Hashmap;

use Maketok\DataMigration\HashmapInterface;

class ArrayHashmap implements HashmapInterface
{
    /**
     * @var string
     */
    private $code;
    /**
     * @var array
     */
    private $storage;

    /**
     * @param string $code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->storage[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->storage[$offset]) ? $this->storage[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->storage[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->storage[$offset] = null;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $storage = [])
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return $this->code;
    }
}
