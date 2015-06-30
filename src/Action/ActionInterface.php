<?php

namespace Maketok\DataMigration\Action;

interface ActionInterface
{
    /**
     * main flow
     */
    public function process();

    /**
     * @return string
     */
    public function getCode();
}
