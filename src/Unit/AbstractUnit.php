<?php

namespace Maketok\DataMigration\Unit;

abstract class AbstractUnit implements UnitInterface
{
    /**
     * @var string
     */
    protected $code;
    /**
     * @var UnitInterface
     */
    protected $parent;

    /**
     * @param string $code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(UnitInterface $parent)
    {
        $this->parent = $parent;
    }
}
