<?php

namespace Maketok\DataMigration;

class ArrayMap implements MapInterface
{
    /**
     * @var array
     */
    protected $state = [];
    /**
     * @var array
     */
    protected $lastFed = [];
    /**
     * @var bool
     */
    protected $frozen = false;

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->state);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->state);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->state[$offset];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->state[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->state[$offset]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function feed(array $row)
    {
        $this->state = array_replace($this->state, array_filter($row));
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh(array $row)
    {
        return $this->lastFed != $row;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->state = [];
        $this->lastFed = [];
    }

    /**
     * {@inheritdoc}
     */
    public function incr($key, $default, $step = 1)
    {
        if (!$this->offsetExists($key)) {
            $current = (int) $default;
        } else {
            $current = $this->offsetGet($key);
            $current += $step;
        }
        $this->offsetSet($key, $current);
        return $current;
    }

    /**
     * getter
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function setState(array $state)
    {
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function frozenIncr($key, $default, $step = 1)
    {
        if (!$this->offsetExists($key)) {
            $current = (int) $default;
        } else {
            $current = $this->offsetGet($key);
            if (!$this->frozen) {
                $current += $step;
            }
        }
        $this->offsetSet($key, $current);
        return $current;
    }

    /**
     * {@inheritdoc}
     */
    public function unFreeze()
    {
        $this->frozen = false;
    }

    /**
     * {@inheritdoc}
     */
    public function freeze()
    {
        $this->frozen = true;
    }
}
