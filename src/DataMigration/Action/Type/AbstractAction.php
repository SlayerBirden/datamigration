<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Unit\UnitBagInterface;

class AbstractAction
{
    /**
     * @var UnitBagInterface
     */
    protected $bag;

    /**
     * @param UnitBagInterface $bag
     */
    public function __construct(UnitBagInterface $bag)
    {
        $this->bag = $bag;
    }
}
