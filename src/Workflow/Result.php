<?php

namespace Maketok\DataMigration\Workflow;

class Result implements ResultInterface
{
    /**
     * @var array
     */
    protected $participants;
    /**
     * @var \DateTime
     */
    protected $startTime;
    /**
     * @var \DateTime
     */
    protected $endTime;

    /**
     * {@inheritdoc}
     */
    public function getTotalRowsProcessed()
    {
        $rows = 0;
        foreach ($this->participants as $key => $data) {
            $rows += $this->getActionProcessed($key);
        }
        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllErrors()
    {
        $errors = [];
        foreach ($this->participants as $key => $data) {
            $errors = array_merge($errors, $this->getActionErrors($key));
        }
        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllExceptions()
    {
        $exceptions = [];
        foreach ($this->participants as $key => $data) {
            $exceptions = array_merge($exceptions, $this->getActionExceptions($key));
        }
        return $exceptions;
    }

    /**
     * {@inheritdoc}
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionProcessed($code)
    {
        return isset($this->participants[$code]['rows_processed']) ?
            $this->participants[$code]['rows_processed'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionErrors($code)
    {
        return isset($this->participants[$code]['errors']) ?
            $this->participants[$code]['errors'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getActionExceptions($code)
    {
        return isset($this->participants[$code]['exceptions']) ?
            $this->participants[$code]['exceptions'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function addActionError($code, $message)
    {
        if (isset($this->participants[$code]['errors'])) {
            $this->participants[$code]['errors'][] = $message;
        } elseif (isset($this->participants[$code])) {
            $this->participants[$code]['errors'] = [$message];
        } else {
            $this->participants[$code] = ['errors' => [$message]];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addActionException($code, \Exception $ex)
    {
        if (isset($this->participants[$code]['exceptions'])) {
            $this->participants[$code]['exceptions'][] = $ex;
        } elseif (isset($this->participants[$code])) {
            $this->participants[$code]['exceptions'] = [$ex];
        } else {
            $this->participants[$code] = ['exceptions' => [$ex]];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function incrementActionProcessed($code, $value = 1)
    {
        if (isset($this->participants[$code]['rows_processed'])) {
            $this->participants[$code]['rows_processed'] += $value;
        } elseif (isset($this->participants[$code])) {
            $this->participants[$code]['rows_processed'] = $value;
        } else {
            $this->participants[$code] = ['rows_processed' => $value];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionStartTime($code)
    {
        return isset($this->participants[$code]['start_time']) ?
            $this->participants[$code]['start_time'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getActionEndTime($code)
    {
        return isset($this->participants[$code]['end_time']) ?
            $this->participants[$code]['end_time'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setStartTime(\DateTime $time)
    {
        $this->startTime = $time;
    }

    /**
     * {@inheritdoc}
     */
    public function setEndTime(\DateTime $time)
    {
        $this->endTime = $time;
    }

    /**
     * {@inheritdoc}
     */
    public function setActionStartTime($code, \DateTime $time)
    {
        if (isset($this->participants[$code])) {
            $this->participants[$code]['start_time'] = $time;
        } else {
            $this->participants[$code] = ['start_time' => $time];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setActionEndTime($code, \DateTime $time)
    {
        if (isset($this->participants[$code])) {
            $this->participants[$code]['end_time'] = $time;
        } else {
            $this->participants[$code] = ['end_time' => $time];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalRowsThrough()
    {
        $rows = [];
        foreach ($this->participants as $key => $data) {
            $rows[] = $this->getActionProcessed($key);
        }
        return min($rows);
    }
}
