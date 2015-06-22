<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\Worker\WorkerBagInterface;

class CreateTmpFiles implements ActionInterface
{
    /**
     * @var WorkerBagInterface
     */
    private $bag;
    /**
     * @var InputResourceInterface
     */
    private $input;

    /**
     * @param WorkerBagInterface $bag
     * @param InputResourceInterface $input
     */
    public function __construct(WorkerBagInterface $bag, InputResourceInterface $input)
    {
        $this->bag = $bag;
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
