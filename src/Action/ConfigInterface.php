<?php

namespace Maketok\DataMigration\Action;

interface ConfigInterface extends \ArrayAccess
{
    /**
     * assign full config
     * @param array $config
     * @return self
     */
    public function assign(array $config);

    /**
     * dump current config
     * @return array
     */
    public function dump();
}
