<?php

namespace Maketok\DataMigration\Unit;

interface ExportFileUnitInterface extends ExportDbUnitInterface
{
    /**
     * get the connection array by which Assembler is going to connect rows
     * @return array
     */
    public function getReversedConnection();

    /**
     * @param array $reversedConnection
     * @return $this
     */
    public function setReversedConnection($reversedConnection);

    /**
     * @return array
     */
    public function getReversedMapping();

    /**
     * @param array $reversedMapping
     * @return $this
     */
    public function setReversedMapping($reversedMapping);
}
