<?php

namespace Maketok\DataMigration\Action;

use Maketok\DataMigration\Workflow\ResultInterface;

interface ActionInterface
{
    /**
     * main flow
     * @param ResultInterface $result
     */
    public function process(ResultInterface $result);

    /**
     * @return string
     */
    public function getCode();
}
