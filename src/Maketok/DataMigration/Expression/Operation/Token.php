<?php

namespace Maketok\DataMigration\Expression\Operation;

class Token
{
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $definition;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }
}
