<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Input\InputResourceInterface;
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
        foreach ($this->bag as $unit) {
            $name = rtrim($this->config->get('tmp_folder'), '/') .
                '/' .
                sprintf(
                    $this->config->get('tmp_file_mask'),
                    $unit->getTable(),
                    date('Y-m-d_H:i:s')
                );
            $this->filesystem->open($name, 'w');
            while (($row = $this->input->get()) !== false) {
                // TODO add mapping, contributions, hashtables
                $this->filesystem->writeRow($row);
            }
            $unit->setTmpFileName($name);
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
