<?php

namespace Maketok\DataMigration;

use Maketok\DataMigration\Action\ActionInterface;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Workflow\ResultInterface;

class QueueWorkflow implements WorkflowInterface
{
    /**
     * @var \SplQueue
     */
    protected $queue;
    /**
     * @var ResultInterface
     */
    protected $result;
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param ConfigInterface $config
     * @param ResultInterface $result
     */
    public function __construct(ConfigInterface $config, ResultInterface $result)
    {
        $this->config = $config;
        $this->result = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function add(ActionInterface $action)
    {
        $this->queue->enqueue($action);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->result->setStartTime(new \DateTime());
        while (!$this->queue->isEmpty()) {
            /** @var ActionInterface $action */
            $action = $this->queue->dequeue();
            $action->process($this->result);
        }
        $this->result->setEndTime(new \DateTime());
    }
}
