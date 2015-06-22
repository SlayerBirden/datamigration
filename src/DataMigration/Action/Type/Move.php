<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Worker\WorkerBagInterface;

class Move implements ActionInterface
{
    /**
     * @var WorkerBagInterface
     */
    private $bag;

    /**
     * @param WorkerBagInterface $bag
     */
    public function __construct(WorkerBagInterface $bag)
    {
        $this->bag = $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // TODO: Implement process() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'move';
    }
}
