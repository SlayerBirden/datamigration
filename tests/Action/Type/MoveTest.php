<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Unit\Type\ImportDbUnit;

class MoveTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new Move(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getResource()
        );
        $this->assertEquals('move', $action->getCode());
    }

    /**
     * @param string $code
     * @return ImportDbUnit
     */
    public function getUnit($code)
    {
        return new ImportDbUnit($code);
    }

    /**
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())
                ->method('move');
        }
        return $resource;
    }

    public function testProcess()
    {
        $unit = $this->getUnit('test');
        $unit->setTmpTable('tmp');
        $action = new Move(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource(true)
        );
        $action->process($this->getResultMock());
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     * @expectedExceptionMessage Action can not be used for current unit test
     */
    public function testWrongProcess()
    {
        $action = new Move(
            $this->getUnitBag([$this->getUnit('test')]),
            $this->getConfig(),
            $this->getResource()
        );
        $action->process($this->getResultMock());
    }
}
