<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;

/**
 * Dump data from table to tmp file
 */
class Dump extends AbstractDbAction implements ActionInterface
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
