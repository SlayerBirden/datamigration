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
     * Get the total number of
     * rows that went through all actions
     * @return int
     */
    public function getTotalRowsThrough();

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
     * @return \DateTime|null
     */
    public function getActionStartTime($code);

    /**
     * @param string $code
     * @return \DateTime|null
     */
    public function getActionEndTime($code);

    /**
     * @param \DateTime $time
     */
    public function setStartTime(\DateTime $time);

    /**
     * @param \DateTime $time
     */
    public function setEndTime(\DateTime $time);

    /**
     * @param string $code
     * @param \DateTime $time
     */
    public function setActionStartTime($code, \DateTime $time);

    /**
     * @param string $code
     * @param \DateTime $time
     */
    public function setActionEndTime($code, \DateTime $time);
}
