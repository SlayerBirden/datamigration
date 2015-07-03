<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;

/**
 * Disperse base input stream into separate units (tmp csv files) for further processing
 */
class CreateTmpFiles extends AbstractAction implements ActionInterface
{
    /**
     * @var InputResourceInterface
     */
    private $input;
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * @var ResourceHelperInterface
     */
    private $helperResource;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     * @param InputResourceInterface $input
     * @param MapInterface $map
     * @param ResourceHelperInterface $helperResource
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem,
                                InputResourceInterface $input,
                                MapInterface $map,
                                ResourceHelperInterface $helperResource)
    {
        parent::__construct($bag, $config, $filesystem);
        $this->input = $input;
        $this->map = $map;
        $this->helperResource = $helperResource;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        foreach ($this->bag as $unit) {
            $unit->setTmpFileName($this->getTmpFileName($unit));
            $this->filesystem->open($unit->getTmpFileName(), 'w');
            while (($row = $this->input->get()) !== false) {
                if (call_user_func_array($unit->getIsEntityCondition(), [
                    'row' => $row,
                ])) {
                    if ($this->map->isFresh($row)) {
                        $this->map->feed($row);
                    }
                    $this->filesystem->writeRow($this->map->dumpState());
                }
            }
            $this->filesystem->close();
            $this->input->reset();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'create_tmp_files';
    }
}
