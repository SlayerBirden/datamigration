<?php

namespace Maketok\DataMigration\Action;

interface ConfigInterface
{
    /**
     * get config key
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * set config key
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set($key, $value);

    /**
     * assign full config
     * @param array $config
     * @return self
     */
    public function assign(array $config);

    /**
     * pull current config
     * @return array
     */
    public function pull();
}
