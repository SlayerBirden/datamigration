<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Storage\Filesystem\ResourceInterface as FsResourceInterface;

class DumpTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new Dump(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getResource()
        );
        $this->assertEquals('dump', $action->getCode());
    }

    /**
     * @param array $returns
     * @return ResourceInterface
     */
    protected function getResource($returns = [])
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        $method = $resource->expects($this->exactly(count($returns)))
            ->method('dumpData');
        call_user_func_array([$method, 'willReturnOnConsecutiveCalls'], $returns);
        return $resource;
    }

    /**
     * @param int $expects
     * @return FsResourceInterface
     */
    protected function getFS($expects = 0)
    {
        $filesystem = $this->getMockBuilder('\Maketok\DataMigration\Storage\Filesystem\ResourceInterface')
            ->getMock();
        $filesystem->expects($this->exactly($expects))
            ->method('writeRow');
        return $filesystem;
    }

    public function testProcess()
    {
        $unit = $this->getUnit('test_table1');
        $unit->setTmpTable('tmp_test_table1');
        $unit->setFilesystem($this->getFS(2));
        $action = new Dump(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource([
                [["1", "somedata"]],
                [["2", "somedata2"]],
                false
            ])
        );
        $action->process($this->getResultMock());

        $this->assertEquals('/tmp/test_table1.csv',
            $unit->getTmpFileName());
    }

    /**
     * @expectedException \Maketok\DataMigration\Action\Exception\WrongContextException
     * @expectedExceptionMessage Action can not be used for current unit test123
     */
    public function testWrongProcess()
    {
        $unit = $this->getUnit('test123');
        $unit->setFilesystem($this->getFS());
        $action = new Dump(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource()
        );
        $action->process($this->getResultMock());
    }
}
