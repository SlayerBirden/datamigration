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
     * @return UnitInterface
     */
    public function getParent();
}
