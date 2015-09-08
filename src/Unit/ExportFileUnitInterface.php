<?php

namespace Maketok\DataMigration\Unit;

interface ExportFileUnitInterface extends UnitInterface
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

    /**
     * @return bool
     */
    public function isOptional();

    /**
     * @param bool $optional
     */
    public function setOptional($optional);
}
