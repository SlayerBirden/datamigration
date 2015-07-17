<?php

namespace Maketok\DataMigration\Workflow;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Result
     */
    private $result;

    public function setUp()
    {
        $this->result = new Result();
    }

    /**
     * @test
     */
    public function testGetAllErrors()
    {
        $this->result->addActionError('test_action', 'Error message1');
        $this->result->addActionError('test_action2', 'Error message2');
        $this->result->addActionError('test_action3', 'Error message1');
        $this->assertSame([
            'Error message1',
            'Error message2',
            'Error message1',
        ], $this->result->getAllErrors());
    }

    /**
     * @test
     */
    public function testGetAllExceptions()
    {
        $e1 = new \Exception('bar');
        $e2 = new \Exception('baz');
        $this->result->addActionException('test_action', $e1);
        $this->result->addActionException('test_action2', $e2);
        $this->assertSame([$e1, $e2], $this->result->getAllExceptions());
    }

    /**
     * @test
     */
    public function testGetParticipants()
    {
        $start1 = new \DateTime();
        $end2 = new \DateTime();
        $e = new \Exception();
        $this->result->setActionStartTime('test1', $start1);
        $this->result->setActionEndTime('test2', $end2);
        $this->result->addActionError('test3', 'error');
        $this->result->addActionException('test2', $e);
        $this->result->incrementActionProcessed('test4');

        $this->assertEquals([
            'test1' => [
                'start_time' => $start1,
            ],
            'test2' => [
                'end_time' => $end2,
                'exceptions' => [$e],
            ],
            'test3' => [
                'errors' => ['error'],
            ],
            'test4' => [
                'rows_processed' => 1,
            ],
        ], $this->result->getParticipants());
    }

    /**
     * @test
     */
    public function testGetTotalRowsProcessed()
    {
        $this->result->incrementActionProcessed('test4', 1);
        $this->result->incrementActionProcessed('test1', 100);

        $this->assertSame(101, $this->result->getTotalRowsProcessed());
    }

    /**
     * @test
     */
    public function testGetTotalRowsThrough()
    {
        $this->result->incrementActionProcessed('test4', 1);
        $this->result->incrementActionProcessed('test1', 100);

        $this->assertSame(1, $this->result->getTotalRowsThrough());
    }
}
