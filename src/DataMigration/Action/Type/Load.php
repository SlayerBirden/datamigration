<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\Exception\WrongContextException;

/**
 * Load data from tmp files to tmp tables
 */
class Load extends AbstractDbAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     * @throws WrongContextException
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
        return 'load';
    }
}
