<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

class Delete extends AbstractAction implements ActionInterface
{
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $resource
     */
    public function __construct(UnitBagInterface $bag, ConfigInterface $config, ResourceInterface $resource)
    {
        parent::__construct($bag, $config);
        $this->resource = $resource;
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
        return 'delete';
    }
}
