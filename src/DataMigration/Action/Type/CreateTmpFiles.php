<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

class CreateTmpFiles extends AbstractAction implements ActionInterface
{
    /**
     * @var InputResourceInterface
     */
    private $input;

    /**
     * @param UnitBagInterface $bag
     * @param InputResourceInterface $input
     */
    public function __construct(UnitBagInterface $bag, InputResourceInterface $input)
    {
        parent::__construct($bag);
        $this->input = $input;
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
        return 'create_tmp_files';
    }
}
