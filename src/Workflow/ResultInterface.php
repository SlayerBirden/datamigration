<?php

namespace Maketok\DataMigration\Workflow;

use Maketok\DataMigration\Unit\UnitBagInterface;

interface ResultInterface
{
    /**
     * Get the total number of
     * rows imported/exported
     * @return int
     */
    public function getTotalRowsProcessed();

    /**
     * Return all errors
     * @return array
     */
    public function getAllErrors();

    /**
     * @return \Exception[]
     */
    public function getAllExceptions();

    /**
     * @return UnitBagInterface
     */
    public function getParticipants();

    /**
     * @param string $code
     * @return int
     */
    public function getActionProcessed($code);

    /**
     * @param string $code
     * @return array
     */
    public function getActionErrors($code);

    /**
     * @param string $code
     * @return \Exception[]
     */
    public function getActionExceptions($code);

    /**
     * @param string $code
     * @param string $message
     */
    public function addActionError($code, $message);

    /**
     * @param string $code
     * @param \Exception $ex
     */
    public function addActionException($code, \Exception $ex);

    /**
     * @param string $code
     * @param int $value
     */
    public function incrementActionProcessed($code, $value = 1);

    /**
     * @return \DateTime
     */
    public function getStartTime();

    /**
     * @return \DateTime
     */
    public function getEndTime();

    /**
     * @param string $code
     * @return \DateTime
     */
    public function getActionStartTime($code);

    /**
     * @param string $code
     * @return \DateTime
     */
    public function getActionEndTime($code);

    /**
     * @param \DateTime $t
     */
    public function setStartTime(\DateTime $t);

    /**
     * @param \DateTime $t
     */
    public function setEndTime(\DateTime $t);

    /**
     * @param string $code
     * @param \DateTime $t
     */
    public function setActionStartTime($code, \DateTime $t);

    /**
     * @param string $code
     * @param \DateTime $t
     */
    public function setActionEndTime($code, \DateTime $t);
}
