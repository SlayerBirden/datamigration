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
     */
    public function setReversedConnection($reversedConnection);

    /**
     * @return array
     */
    public function getReversedMapping();

    /**
     * @param array $reversedMapping
     */
    public function setReversedMapping($reversedMapping);
}
