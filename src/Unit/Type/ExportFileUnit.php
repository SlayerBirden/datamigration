<?php

namespace Maketok\DataMigration\Unit\Type;

use Maketok\DataMigration\Unit\ExportFileUnitInterface;

class ExportFileUnit extends ExportDbUnit implements ExportFileUnitInterface
{
    /**
     * @var array
     */
    protected $reversedMapping;
    /**
     * @var array
     */
    protected $reversedConnection;

    /**
     * {@inheritdoc}
     */
    public function getReversedMapping()
    {
        return $this->reversedMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function setReversedMapping($reversedMapping)
    {
        $this->reversedMapping = $reversedMapping;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReversedConnection()
    {
        return $this->reversedConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function setReversedConnection($reversedConnection)
    {
        $this->reversedConnection = $reversedConnection;
        return $this;
    }
}
