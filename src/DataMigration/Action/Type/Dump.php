<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;

class Dump extends AbstractAction implements ActionInterface
{
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
        return 'dump';
    }
}