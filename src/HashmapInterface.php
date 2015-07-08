<?php

namespace Maketok\DataMigration;

interface HashmapInterface extends \ArrayAccess
{
    /**
     * populate internal hashmap array
     * or open the connection to hashmap resource
     * @return mixed
     */
    public function load();

    /**
     * add magic getter for hashmap keys
     * @param string $offset
     * @return mixed
     */
    public function __get($offset);

    /**
     * add magic setter of hashmap valued
     * @param string $offset
     * @param mixed $value
     */
    public function __set($offset, $value);

    /**
     * get hashmap identifier
     * @return string
     */
    public function getCode();
}
