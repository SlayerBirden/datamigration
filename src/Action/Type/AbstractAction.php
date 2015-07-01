<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface;
use Maketok\DataMigration\Unit\UnitBagInterface;
use Maketok\DataMigration\Unit\AbstractUnit;

class AbstractAction
{
    /**
     * @var UnitBagInterface|AbstractUnit[]
     */
    protected $bag;
    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var ResourceInterface
     */
    protected $filesystem;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     * @param ResourceInterface $filesystem
     */
    public function __construct(UnitBagInterface $bag,
                                ConfigInterface $config,
                                ResourceInterface $filesystem)
    {
        $this->bag = $bag;
        $this->config = $config;
        $this->filesystem = $filesystem;
    }

    /**
     * @param AbstractUnit $unit
     * @return string
     */
    public function getTmpFileName(AbstractUnit $unit)
    {
        return rtrim($this->config->get('tmp_folder'), '/') .
        '/' .
        sprintf(
            $this->config->get('tmp_file_mask'),
            $unit->getTable(),
            date('Y-m-d_H:i:s')
        );
    }

    /**
     * @param AbstractUnit $unit
     * @return string
     */
    public function getTmpTableName(AbstractUnit $unit)
    {
        return sprintf(
            $this->config->get('tmp_table_mask'),
            $unit->getTable(),
            implode(explode(" ", microtime()))
        );
    }
}
