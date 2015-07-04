<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Action\ConfigInterface;
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
     * @var \DateTime
     */
    protected $date;

    /**
     * @param UnitBagInterface $bag
     * @param ConfigInterface $config
     */
    public function __construct(
        UnitBagInterface $bag,
        ConfigInterface $config
    ) {
        $this->bag = $bag;
        $this->config = $config;
        $this->date = new \DateTime();
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
            $this->getDate()
        );
    }

    /**
     * @return string
     */
    protected function getDate()
    {
        return $this->date->format('Y-m-d_H:i:s');
    }

    /**
     * @return int
     */
    protected function getStamp()
    {
        return $this->date->getTimestamp();
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
            $this->getStamp()
        );
    }
}
