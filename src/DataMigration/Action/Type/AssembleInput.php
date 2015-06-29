<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

/**
 * Create Base Input stream from separate units
 */
class AssembleInput extends AbstractAction implements ActionInterface
{
    /**
     * @var InputResourceInterface
     */
    private $input;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     * @param InputResourceInterface $input
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem,
                                InputResourceInterface $input)
    {
        parent::__construct($bag, $config, $filesystem);
        $this->input = $input;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // TODO implement
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'assemble_input';
    }
}
