<?php

namespace Maketok\DataMigration\Action\Exception;

class ConflictException extends \Exception
{
    /**
     * @var array
     */
    private $unitsInConflict;
    /**
     * @var string
     */
    private $conflictedKey;

    /**
     * @param string $message
     * @param array $unitsInConflict
     * @param string $conflictedKey
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(
        $message,
        array $unitsInConflict,
        $conflictedKey,
        $code = 0,
        \Exception $previous = null
    ) {
        $this->unitsInConflict = $unitsInConflict;
        parent::__construct($message, $code, $previous);
        $this->conflictedKey = $conflictedKey;
    }

    /**
     * @return array
     */
    public function getUnitsInConflict()
    {
        return $this->unitsInConflict;
    }

    /**
     * @param array $unitsInConflict
     * @return $this
     */
    public function setUnitsInConflict($unitsInConflict)
    {
        $this->unitsInConflict = $unitsInConflict;
        return $this;
    }

    /**
     * @return array
     */
    public function getConflictedKey()
    {
        return $this->conflictedKey;
    }

    /**
     * @param array $conflictedKey
     * @return $this
     */
    public function setConflictedKey($conflictedKey)
    {
        $this->conflictedKey = $conflictedKey;
        return $this;
    }
}
