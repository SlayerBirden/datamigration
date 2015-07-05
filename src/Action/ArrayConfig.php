<?php

namespace Maketok\DataMigration\Action;

class ArrayConfig implements ConfigInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function assign(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function dump()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->config[$offset];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->config[$offset]);
        }
        return null;
    }
}
