<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Action\ActionInterface;

interface WorkflowInterface
{
    /**
     * @param ActionInterface $action
     * @return self
     */
    public function add(ActionInterface $action);

    /**
     * execute workflow
     */
    public function execute();
}
