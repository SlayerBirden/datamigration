<?php

namespace Maketok\DataMigration\Unit;

interface UnitInterface
{
    /**
     * Get main identifier
     * @return string
     */
    public function getCode();

    /**
     * @param UnitInterface $parent
     */
    public function setParent(UnitInterface $parent);

    /**
     * @return UnitInterface|null
     */
    public function getParent();

    /**
     * @param UnitInterface $sibling
     * @param bool $addBack
     */
    public function addSibling(UnitInterface $sibling, $addBack = true);

    /**
     * @return UnitInterface[]
     */
    public function getSiblings();
}
