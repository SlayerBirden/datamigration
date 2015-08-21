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
     * @var UnitInterface[]
     */
    protected $siblings = [];

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

    /**
     * {@inheritdoc}
     * @var bool $addSiblingBack
     */
    public function addSibling(UnitInterface $sibling, $addBack = true, $addSiblingBack = true)
    {
        if ($addSiblingBack) {
            /** @var AbstractUnit $oldSib */
            foreach ($this->siblings as $oldSib) {
                $oldSib->addSibling($sibling, true, false);
            }
        }
        $this->siblings[$sibling->getCode()] = $sibling;
        if ($addBack) {
            $sibling->addSibling($this, false, false);
        }
    }

    /**
     * @return UnitInterface[]
     */
    public function getSiblings()
    {
        return $this->siblings;
    }
}
