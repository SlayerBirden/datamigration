<?php

namespace Maketok\DataMigration\Worker\Operation\Resource;

interface ResourceInterface
{
    /**
     * @param string $source
     */
    public function setSource($source);

    /**
     * @param string $destination
     */
    public function setDestination($destination);
}
